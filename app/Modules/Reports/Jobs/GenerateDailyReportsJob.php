<?php

namespace App\Modules\Reports\Jobs;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Reports\Models\BookingReport;
use App\Modules\Reports\Models\ProviderPerformanceReport;
use App\Modules\Reports\Models\SalesReport;
use App\Modules\Reports\Models\TopSellingProductReport;
use App\Modules\Reviews\Models\Review;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Scheduled nightly. Report endpoints read exclusively from the tables this
 * job populates - never live-querying Orders/Bookings/Reviews directly, so
 * report pages stay fast regardless of transactional table size. Re-running
 * for the same tenant+date upserts (safe to re-run, e.g. after a fix).
 */
class GenerateDailyReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $date = $this->date ? Carbon::parse($this->date) : now()->subDay();
        $dateString = $date->toDateString();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        Tenant::withoutGlobalScopes()->chunkById(50, function ($tenants) use ($dateString, $start, $end) {
            foreach ($tenants as $tenant) {
                $this->generateSalesReport($tenant->id, $dateString, $start, $end);
                $this->generateBookingReport($tenant->id, $dateString, $start, $end);
                $this->generateProviderPerformanceReport($tenant->id, $dateString, $start, $end);
                $this->generateTopSellingReport($tenant->id, $dateString, $start, $end);
            }
        });
    }

    protected function generateSalesReport(string $tenantId, string $dateString, Carbon $start, Carbon $end): void
    {
        $orders = Order::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $gross = $orders->sum('subtotal');
        $discount = $orders->sum('discount_total');
        $tax = $orders->sum('tax_total');

        SalesReport::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'report_date' => $dateString],
            [
                'orders_count' => $orders->count(),
                'gross_revenue' => $gross,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'net_revenue' => $gross - $discount + $tax,
            ]
        );
    }

    protected function generateBookingReport(string $tenantId, string $dateString, Carbon $start, Carbon $end): void
    {
        $bookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $completed = $bookings->where('status', 'completed');

        BookingReport::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'report_date' => $dateString],
            [
                'bookings_count' => $bookings->count(),
                'completed_count' => $completed->count(),
                'cancelled_count' => $bookings->where('status', 'cancelled')->count(),
                'gross_revenue' => $completed->sum(fn ($b) => $b->price + $b->emergency_charge),
            ]
        );
    }

    protected function generateProviderPerformanceReport(string $tenantId, string $dateString, Carbon $start, Carbon $end): void
    {
        $bookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $completedCount = $bookings->where('status', 'completed')->count();
        $cancelledCount = $bookings->where('status', 'cancelled')->count();
        $totalCount = $bookings->count();

        $reviews = Review::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        ProviderPerformanceReport::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'report_date' => $dateString],
            [
                'avg_overall_rating' => $reviews->isNotEmpty() ? round($reviews->avg('overall_rating'), 2) : null,
                'reviews_count' => $reviews->count(),
                'completed_bookings_count' => $completedCount,
                'cancelled_bookings_count' => $cancelledCount,
                'cancellation_rate' => $totalCount > 0 ? round($cancelledCount / $totalCount * 100, 2) : 0,
            ]
        );
    }

    protected function generateTopSellingReport(string $tenantId, string $dateString, Carbon $start, Carbon $end): void
    {
        TopSellingProductReport::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('report_date', $dateString)
            ->delete();

        $topProducts = OrderItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$start, $end])->whereNotIn('status', ['cancelled']))
            ->whereNotNull('product_id')
            ->selectRaw('product_id, product_name, SUM(quantity) as units_sold, SUM(line_total) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get();

        foreach ($topProducts as $rank => $row) {
            TopSellingProductReport::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'report_date' => $dateString,
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'units_sold' => $row->units_sold,
                'revenue' => $row->revenue,
                'rank' => $rank + 1,
            ]);
        }
    }
}
