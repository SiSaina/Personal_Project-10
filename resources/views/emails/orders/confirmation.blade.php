<h1>Thanks for your order</h1>
<p>Your order number is <strong>#{{ $order->id }}</strong>.</p>
<p>Payment method: {{ str_replace('_', ' ', $order->payment_method) }}</p>
<ul>
@foreach ($order->items as $item)
    <li>{{ $item->product_name }} × {{ $item->quantity }} — ${{ $item->line_total }}</li>
@endforeach
</ul>
<p>Subtotal: ${{ $order->subtotal }}</p>
@if ((float) $order->discount_total > 0)<p>Discount: -${{ $order->discount_total }}</p>@endif
<p><strong>Total: ${{ $order->total }}</strong></p>
