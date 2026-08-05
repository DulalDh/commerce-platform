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
        Schema::create('commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('order_total', 12, 2);
            $table->decimal('rate', 5, 4);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'settled'])->default('pending');
            $table->timestamps();

            // One commission row per order - guards against the calculation
            // service ever double-charging the same order on a re-run.
            $table->unique('order_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
