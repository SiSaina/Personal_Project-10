@extends('admin.layout')
@section('title', 'Addresses')
@section('content')
<h2>Addresses</h2>
<p><a href="{{ route('admin.addresses.create') }}">Add address</a></p>
<form method="GET" class="inline">
    <label for="search">Recipient, street, city or user email</label>
    <input id="search" name="search" value="{{ $search }}" maxlength="100">
    <button>Search</button><a href="{{ route('admin.addresses') }}">Clear</a>
</form>
<div class="table-wrap"><table>
    <thead><tr><th>ID</th><th>User</th><th>Recipient</th><th>Address</th><th>Action</th></tr></thead>
    <tbody>@forelse($addresses as $address)
        <tr><td>{{ $address->id }}</td><td>{{ $address->user?->name ?? 'User unavailable' }}<br>{{ $address->user?->email }}</td>
        <td>{{ $address->full_name }}</td><td>{{ $address->street_name }}<br>{{ $address->suburb }}, {{ $address->city }} {{ $address->postal_code }}<br>{{ $address->country }}</td>
        <td><a href="{{ route('admin.addresses.edit', $address) }}">Edit</a></td></tr>
    @empty<tr><td colspan="5">No addresses found.</td></tr>@endforelse</tbody>
</table></div>
<p>Page {{ $addresses->currentPage() }} of {{ $addresses->lastPage() }} · {{ $addresses->total() }} addresses</p>
@if($addresses->previousPageUrl())<a href="{{ $addresses->previousPageUrl() }}">Previous</a>@endif
@if($addresses->nextPageUrl())<a href="{{ $addresses->nextPageUrl() }}">Next</a>@endif
@endsection
