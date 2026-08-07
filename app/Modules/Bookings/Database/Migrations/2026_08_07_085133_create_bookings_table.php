<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('status', [
                'pending', 'accepted', 'assigned', 'on_the_way', 'started',
                'completed', 'cancelled', 'refunded',
            ])->default('pending');
            $table->date('scheduled_date');
            $table->time('scheduled_slot_start');
            $table->time('scheduled_slot_end');
            $table->jsonb('address');
            $table->boolean('is_emergency')->default(false);
            $table->decimal('price', 12, 2);
            $table->decimal('emergency_charge', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_date']);
        });

        // Partial unique index: a provider (tenant) can only hold one
        // non-cancelled booking per date+slot. This is the actual
        // concurrency backstop - the app-level AvailabilityCheckService is
        // just a fast, friendly pre-check; this index is what makes it
        // impossible for two simultaneous requests to both succeed.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX bookings_provider_slot_unique
            ON bookings (tenant_id, scheduled_date, scheduled_slot_start)
            WHERE status NOT IN ('cancelled', 'refunded')
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
