@extends('admin.layout')
@section('content')
<h2>Roles</h2>
<p>The application supports three fixed roles. Assign them through <a href="{{ route('admin.users') }}">Users</a>. Role names cannot be renamed or deleted here because access controls depend on them.</p>
<table><thead><tr><th>Role</th><th>Users</th><th>Admin panel access</th></tr></thead><tbody>
@foreach($roles as $role)<tr><td>{{ $role->role_type }}</td><td><a href="{{ route('admin.users', ['role' => $role->id]) }}">{{ $role->users_count }} users</a></td><td>{{ $role->role_type === 'Admin' ? 'Yes' : 'No' }}</td></tr>@endforeach
</tbody></table>
<form method="POST" action="{{ route('admin.roles.ensure') }}">@csrf<button>Create any missing supported roles</button></form>
@endsection
