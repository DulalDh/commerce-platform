<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            // idempotency_key: set by us at initiate() time, returned to the
            // gateway, and expected back on the webhook - our own dedup key
            // independent of whatever reference format the gateway uses.
            $table->uuid('idempotency_key')->unique();
            // gateway_reference: the gateway's own transaction id. Unique
            // per gateway so a retried webhook for the same transaction
            // can never create a second row - the retry just re-finds this
            // one and the handler no-ops instead of double-fulfilling.
            $table->string('gateway_reference')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_reference']);
            $table->index(['tenant_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
