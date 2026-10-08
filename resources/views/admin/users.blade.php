@extends('admin.layout')
@section('content')
<h2>Users</h2>
<p><a href="{{ route('admin.users.create') }}">Add user</a></p>
<form method="GET" class="inline">
<label for="search">Name or email</label><input id="search" name="search" value="{{ $search }}" maxlength="100">
<label for="role">Role</label><select id="role" name="role"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($roleId == $role->id)>{{ $role->role_type }}</option>@endforeach</select>
<button>Search</button><a href="{{ route('admin.users') }}">Clear</a>
</form>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Action</th></tr></thead><tbody>
@forelse($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->phone }}</td><td>{{ $user->role?->role_type ?? 'Unassigned' }}</td><td><a href="{{ route('admin.users.edit', $user) }}">Edit</a></td></tr>
@empty<tr><td colspan="5">No users found.</td></tr>@endforelse
</tbody></table></div>
<p>Page {{ $users->currentPage() }} of {{ $users->lastPage() }} · {{ $users->total() }} users</p>
@if($users->previousPageUrl())<a href="{{ $users->previousPageUrl() }}">Previous</a>@endif
@if($users->nextPageUrl())<a href="{{ $users->nextPageUrl() }}">Next</a>@endif
@endsection
