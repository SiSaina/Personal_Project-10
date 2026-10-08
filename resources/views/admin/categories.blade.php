@extends('admin.layout')
@section('content')
<h2>Categories</h2>
<form method="POST" action="{{ route('admin.categories.store') }}" class="inline">
@csrf<label for="new-name">New category</label><input id="new-name" name="name" value="{{ old('name') }}" maxlength="255" required><button>Add category</button>
</form>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Products</th><th>Edit</th></tr></thead><tbody>
@forelse($categories as $category)
<tr><td>{{ $category->name }}</td><td>{{ $category->products_count }}</td><td><form class="inline" method="POST" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PUT')<input aria-label="Name for {{ $category->name }}" name="name" value="{{ $category->name }}" maxlength="255" required><button>Save</button></form></td></tr>
@empty<tr><td colspan="3">No categories yet.</td></tr>@endforelse
</tbody></table></div>
<p>Page {{ $categories->currentPage() }} of {{ $categories->lastPage() }}</p>
@if($categories->previousPageUrl())<a href="{{ $categories->previousPageUrl() }}">Previous</a>@endif
@if($categories->nextPageUrl())<a href="{{ $categories->nextPageUrl() }}">Next</a>@endif
@endsection
