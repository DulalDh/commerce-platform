<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $orderId) {}

    public function handle(): void
    {
        $order = Order::withoutGlobalScopes()->with('items', 'user')->find($this->orderId);

        if (! $order) {
            return;
        }

        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);

        Storage::disk('s3')->put(
            "tenants/{$order->tenant_id}/invoices/{$order->order_number}.pdf",
            $pdf->output()
        );
    }
}
