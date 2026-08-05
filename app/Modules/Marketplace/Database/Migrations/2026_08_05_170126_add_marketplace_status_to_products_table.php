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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('marketplace_status', ['not_submitted', 'pending', 'approved', 'rejected'])
                ->default('not_submitted')->after('published_to_marketplace');
            $table->text('marketplace_rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['marketplace_status', 'marketplace_rejection_reason']);
        });
    }
};
