@extends('admin.layout')
@section('title', 'Order details')
@section('content')
<p><a href="{{ route('admin.orders') }}">Back to orders</a></p>
<h2>Order #{{ $order->id }}</h2>
<p>This page is view only. Order items show the names and prices saved at checkout.</p>
<dl>
    <dt>Buyer</dt><dd>{{ $order->user?->name ?? 'User unavailable' }} — {{ $order->user?->email }}</dd>
    <dt>Placed</dt><dd>{{ $order->placed_at?->format('Y-m-d H:i') }}</dd>
    <dt>Order status</dt><dd>{{ $order->status }}</dd>
    <dt>Payment</dt><dd>{{ $order->payment_status }} — {{ $order->payment_method }}</dd>
    <dt>Delivery</dt><dd>{{ $order->fulfillment_status }}</dd>
    <dt>Shipped</dt><dd>{{ $order->shipped_at?->format('Y-m-d H:i') ?? 'Not shipped' }}</dd>
    <dt>Delivered</dt><dd>{{ $order->delivered_at?->format('Y-m-d H:i') ?? 'Not delivered' }}</dd>
</dl>
<h3>Delivery address</h3>
@if($order->address)
    <p>{{ $order->address->full_name }}<br>{{ $order->address->street_name }}<br>
    {{ $order->address->suburb }}, {{ $order->address->city }} {{ $order->address->postal_code }}<br>{{ $order->address->country }}</p>
    <p><a href="{{ route('admin.addresses.edit', $order->address) }}">Edit address</a></p>
@else
    <p>Address unavailable.</p>
@endif
<h3>Order items</h3>
<div class="table-wrap"><table>
    <thead><tr><th>Item ID</th><th>Product</th><th>Unit price</th><th>Quantity</th><th>Line total</th></tr></thead>
    <tbody>@forelse($order->items as $item)
        <tr><td>{{ $item->id }}</td><td>{{ $item->product_name }}
        @if($item->product)
            <br><a href="{{ route('admin.products.edit', $item->product) }}">Current product</a>
        @else
            <br>Product unavailable
        @endif
        </td><td>{{ $item->unit_price }}</td><td>{{ $item->quantity }}</td><td>{{ $item->line_total }}</td></tr>
    @empty<tr><td colspan="5">No order items found.</td></tr>@endforelse</tbody>
</table></div>
<p>Subtotal: {{ $order->subtotal }}<br>Discount: {{ $order->discount_total }}<br>
Coupon: {{ $order->coupon_code ?? 'None' }}<br>Total: {{ $order->total }}</p>
@endsection
