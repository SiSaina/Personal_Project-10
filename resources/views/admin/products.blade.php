@extends('admin.layout')
@section('content')
<h2>Products</h2>
<p><a href="{{ route('admin.products.create') }}">Add product</a></p>
<form method="GET" class="inline"><label for="search">Search name</label><input id="search" name="search" value="{{ $search }}" maxlength="100"><button>Search</button><a href="{{ route('admin.products') }}">Clear</a></form>
<div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Offer price</th><th>Action</th></tr></thead>
<tbody>@forelse($products as $product)
<tr><td>{{ $product->name }}</td><td>{{ $product->category?->name }}</td><td>{{ number_format($product->price, 2) }}</td><td>{{ number_format($product->offer_price, 2) }}</td><td><a href="{{ route('admin.products.edit', $product) }}">Edit</a></td></tr>
@empty<tr><td colspan="5">No products found.</td></tr>@endforelse</tbody>
</table></div>
<p>Page {{ $products->currentPage() }} of {{ $products->lastPage() }} · {{ $products->total() }} products</p>
@if($products->previousPageUrl())<a href="{{ $products->previousPageUrl() }}">Previous</a>@endif
@if($products->nextPageUrl())<a href="{{ $products->nextPageUrl() }}">Next</a>@endif
@endsection
