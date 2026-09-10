<h1>Order #{{ $order->id }} update</h1>
<p>Your order is now <strong>{{ $order->fulfillment_status }}</strong>.</p>
@if ($order->fulfillment_status === 'shipped')<p>Your order has left the seller and is on its way.</p>@endif
<p>Total: ${{ $order->total }}</p>
