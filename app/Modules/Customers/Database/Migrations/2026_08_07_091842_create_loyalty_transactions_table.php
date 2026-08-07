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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earned', 'redeemed']);
            $table->integer('points');
            $table->integer('points_after');
            $table->string('reference')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('loyalty_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
