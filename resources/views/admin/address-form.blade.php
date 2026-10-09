@extends('admin.layout')
@section('title', $address->exists ? 'Edit address' : 'Add address')
@section('content')
<h2>{{ $address->exists ? 'Edit address' : 'Add address' }}</h2>
<p>Addresses are shared with orders that use them. Editing an address also changes the address shown on those orders.</p>
<form method="POST" class="editor" action="{{ $address->exists ? route('admin.addresses.update', $address) : route('admin.addresses.store') }}">
    @csrf
    @if($address->exists) @method('PUT') @endif
    <label for="user_id">User</label>
    <select id="user_id" name="user_id" required>
        <option value="">Choose a user</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('user_id', $address->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
    @foreach(['full_name' => 'Full name', 'postal_code' => 'Postal code', 'street_name' => 'Street', 'suburb' => 'Suburb', 'city' => 'City', 'country' => 'Country'] as $field => $label)
        <label for="{{ $field }}">{{ $label }}</label>
        <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $address->$field) }}" maxlength="{{ $field === 'postal_code' ? 10 : 255 }}" required>
    @endforeach
    <p class="actions"><button>Save address</button> <a href="{{ route('admin.addresses') }}">Cancel</a></p>
</form>
@endsection
