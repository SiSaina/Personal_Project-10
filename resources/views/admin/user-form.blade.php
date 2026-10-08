@extends('admin.layout')
@section('content')
<h2>{{ $user->exists ? 'Edit user' : 'Add user' }}</h2>
<form class="editor" method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
@csrf @if($user->exists)@method('PUT')@endif
<label for="name">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
<label for="phone">Phone (optional)</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="15">
<label for="role_id">Role</label><select id="role_id" name="role_id" required><option value="">Select role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->role_type }}</option>@endforeach</select>
@if($user->exists)<p>Leave password fields blank to keep the current password. Changing email, password, or role revokes API login tokens.</p>@endif
<label for="password">{{ $user->exists ? 'New password (optional)' : 'Password' }}</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" @required(!$user->exists)>
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" @required(!$user->exists)>
<div class="actions"><button>Save user</button> <a href="{{ route('admin.users') }}">Cancel</a></div>
</form>
@endsection
