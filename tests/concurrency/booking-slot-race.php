<?php

/**
 * Standalone concurrency probe for the Phase 3 testing gate: "two customers
 * can't book the same provider/slot simultaneously - DB-level locking or
 * unique constraint, not just application checks."
 *
 * This runs outside Pest/PHPUnit deliberately: it forks real OS processes
 * that each need their own live, committed DB connection to prove the
 * guarantee holds at the database level, not just via
 * AvailabilityCheckService's app-level pre-check. That's incompatible with
 * RefreshDatabase's single wrapping test transaction (a forked child's
 * fresh connection can't see the parent's uncommitted fixture rows), so it
 * manages its own fixtures and cleanup against the real dev/test database.
 *
 * Run: php artisan tinker --execute="require 'tests/concurrency/booking-slot-race.php';"
 * or directly:   php tests/concurrency/booking-slot-race.php
 */

use App\Models\User;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Services\BookingService;
use App\Modules\Services\Models\Service;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function fail(string $message): never
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function pass(string $message): void
{
    echo "PASS: {$message}\n";
}

if (! function_exists('pcntl_fork')) {
    fwrite(STDERR, "SKIP: pcntl extension not available - cannot simulate true concurrency.\n");
    exit(0);
}

$tenant = Tenant::create([
    'name' => 'Concurrency Probe Co', 'type' => 'service_provider', 'status' => 'active',
    'subdomain' => 'concurrency-probe-'.uniqid(),
]);
$service = Service::create([
    'tenant_id' => $tenant->id, 'name' => 'Repair', 'slug' => 'repair-'.uniqid(),
    'pricing_model' => 'fixed', 'price' => 50,
]);
$customerA = User::create([
    'tenant_id' => null, 'name' => 'Customer A', 'email' => 'concurrency-a-'.uniqid().'@example.test',
    'password' => bcrypt('password'),
]);
$customerB = User::create([
    'tenant_id' => null, 'name' => 'Customer B', 'email' => 'concurrency-b-'.uniqid().'@example.test',
    'password' => bcrypt('password'),
]);

$cleanup = function () use ($tenant, $service, $customerA, $customerB) {
    Booking::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
    $service->delete();
    $customerA->delete();
    $customerB->delete();
    $tenant->delete();
};

// Deliberately NOT registered as a shutdown function: pcntl_fork() clones
// the whole process, including registered shutdown callbacks, so each
// child would run this on its own exit() and delete the very booking row
// it just (maybe) created before the parent gets a chance to check. Called
// explicitly in the parent only, at the very end of the script instead.

$date = '2027-01-15';
$slotStart = '09:00';
$slotEnd = '10:00';

$resultFileA = tempnam(sys_get_temp_dir(), 'booking_race_a_');
$resultFileB = tempnam(sys_get_temp_dir(), 'booking_race_b_');

$attempt = function (User $customer, string $resultFile) use ($service, $date, $slotStart, $slotEnd, $app) {
    // Forked children inherit the parent's PDO connection, which is not
    // safe to share across processes - drop it so each child gets its own
    // fresh, independent connection to the real (committed) DB.
    DB::purge();

    try {
        $app->make(BookingService::class)->create(
            $customer->id, $service->id, $date, $slotStart, $slotEnd,
            ['line1' => 'Race St', 'city' => 'City'],
        );
        file_put_contents($resultFile, 'success');
    } catch (Throwable $e) {
        file_put_contents($resultFile, 'failure:'.$e->getMessage());
    }
};

$pidA = pcntl_fork();
if ($pidA === -1) {
    fail('pcntl_fork failed for process A.');
} elseif ($pidA === 0) {
    $attempt($customerA, $resultFileA);
    exit(0);
}

$pidB = pcntl_fork();
if ($pidB === -1) {
    fail('pcntl_fork failed for process B.');
} elseif ($pidB === 0) {
    $attempt($customerB, $resultFileB);
    exit(0);
}

// Parent's connection is now stale relative to what the children just
// committed independently - drop it so the verification queries below see
// their writes.
DB::purge();

pcntl_waitpid($pidA, $statusA);
pcntl_waitpid($pidB, $statusB);

$resultA = file_get_contents($resultFileA);
$resultB = file_get_contents($resultFileB);
unlink($resultFileA);
unlink($resultFileB);

$outcomes = [$resultA, $resultB];
$successes = array_filter($outcomes, fn ($r) => $r === 'success');
$failures = array_filter($outcomes, fn ($r) => str_starts_with($r, 'failure'));

if (count($successes) !== 1) {
    $cleanup();
    fail('Expected exactly 1 successful booking, got '.count($successes).'. Outcomes: '.implode(' | ', $outcomes));
}
pass('Exactly one of the two racing requests succeeded.');

if (count($failures) !== 1) {
    $cleanup();
    fail('Expected exactly 1 failed booking, got '.count($failures).'. Outcomes: '.implode(' | ', $outcomes));
}
pass('Exactly one of the two racing requests failed cleanly.');

$bookingsForSlot = Booking::withoutGlobalScopes()
    ->where('tenant_id', $tenant->id)
    ->where('scheduled_date', $date)
    ->where('scheduled_slot_start', $slotStart)
    ->whereNotIn('status', ['cancelled', 'refunded'])
    ->count();

if ($bookingsForSlot !== 1) {
    $cleanup();
    fail("Expected exactly 1 booking row for the contested slot, found {$bookingsForSlot}.");
}
pass('Exactly one booking row exists in the database for the contested slot.');

$cleanup();

echo "\nAll concurrency assertions passed - the partial unique index enforces this at the DB level, independent of the app-level pre-check.\n";
