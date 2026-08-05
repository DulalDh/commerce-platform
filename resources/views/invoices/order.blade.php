<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        h1 { font-size: 18px; }
        .totals td { border: none; }
        .totals tr:last-child td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Invoice — {{ $order->order_number }}</h1>
    <p>Status: {{ $order->status }}<br>Date: {{ $order->created_at->toFormattedDateString() }}</p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</td></tr>
        <tr><td>Discount</td><td>-{{ number_format($order->discount_total, 2) }} {{ $order->currency }}</td></tr>
        <tr><td>Shipping</td><td>{{ number_format($order->shipping_total, 2) }} {{ $order->currency }}</td></tr>
        <tr><td>Tax</td><td>{{ number_format($order->tax_total, 2) }} {{ $order->currency }}</td></tr>
        <tr><td>Total</td><td>{{ number_format($order->total, 2) }} {{ $order->currency }}</td></tr>
    </table>
</body>
</html>
