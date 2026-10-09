@extends('admin.layout')
@section('title', 'Orders')
@section('content')
<h2>Orders</h2>
<form method="GET" class="inline">
    <label for="search">Order ID, buyer name or email</label>
    <input id="search" name="search" value="{{ $search }}" maxlength="100">
    <button>Search</button><a href="{{ route('admin.orders') }}">Clear</a>
</form>
<div class="table-wrap"><table>
    <thead><tr><th>Order</th><th>Buyer</th><th>Placed</th><th>Items</th><th>Total</th><th>Payment</th><th>Delivery</th><th>Action</th></tr></thead>
    <tbody>@forelse($orders as $order)
        <tr><td>#{{ $order->id }}</td><td>{{ $order->user?->name ?? 'User unavailable' }}<br>{{ $order->user?->email }}</td>
        <td>{{ $order->placed_at?->format('Y-m-d H:i') }}</td><td>{{ $order->items_count }}</td>
        <td>{{ $order->total }}</td><td>{{ $order->payment_status }}</td><td>{{ $order->fulfillment_status }}</td>
        <td><a href="{{ route('admin.orders.show', $order) }}">View details</a></td></tr>
    @empty<tr><td colspan="8">No orders found.</td></tr>@endforelse</tbody>
</table></div>
<p>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }} · {{ $orders->total() }} orders</p>
@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">Previous</a>@endif
@if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Next</a>@endif
@endsection
