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
        Schema::create('top_selling_product_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('units_sold')->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->unsignedInteger('rank')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'report_date', 'product_id']);
            $table->index(['tenant_id', 'report_date', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('top_selling_product_reports');
    }
};
