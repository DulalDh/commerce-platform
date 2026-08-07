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
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('years_experience')->nullable();
            // {"cities": [...], "radius_km": 15}
            $table->jsonb('service_area')->nullable();
            // [{"id": uuid, "name": "...", "type": "...", "path": "...", "uploaded_at": "..."}]
            $table->jsonb('certificates')->nullable();
            $table->enum('verification_status', ['not_submitted', 'pending', 'verified', 'rejected'])
                ->default('not_submitted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
