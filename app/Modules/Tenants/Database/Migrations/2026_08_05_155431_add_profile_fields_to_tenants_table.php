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
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->jsonb('address')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('theme_color', 7)->nullable();

            $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])
                ->default('not_submitted');
            $table->jsonb('kyc_documents')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'contact_email', 'contact_phone', 'address',
                'logo_path', 'banner_path', 'theme_color',
                'kyc_status', 'kyc_documents',
            ]);
        });
    }
};
