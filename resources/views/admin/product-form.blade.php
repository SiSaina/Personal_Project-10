@extends('admin.layout')
@section('content')
<h2>{{ $product->exists ? 'Edit product' : 'Add product' }}</h2>
@if($categories->isEmpty())<p>Create a <a href="{{ route('admin.categories') }}">category</a> first.</p>@endif
<form class="editor" method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
@csrf
@if($product->exists)@method('PUT')@endif
<label for="name">Name</label><input id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required>
<label for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>
<label for="description">Description</label><textarea id="description" name="description" maxlength="255" rows="4" required>{{ old('description', $product->description) }}</textarea>
<label for="price">Price</label><input id="price" type="number" name="price" min="0" max="99999999.99" step="0.01" value="{{ old('price', $product->price) }}" required>
<label for="offer_price">Offer price (must not exceed price)</label><input id="offer_price" type="number" name="offer_price" min="0" max="99999999.99" step="0.01" value="{{ old('offer_price', $product->offer_price) }}" required>
<label for="date">Date</label><input id="date" type="date" name="date" value="{{ old('date', $product->date ?? now()->toDateString()) }}" required>
<div class="actions"><button @disabled($categories->isEmpty())>Save product</button> <a href="{{ route('admin.products') }}">Cancel</a></div>
</form>
@endsection
