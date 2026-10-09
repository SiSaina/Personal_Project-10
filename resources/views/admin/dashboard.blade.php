@extends('admin.layout')
@section('content')
<h2>Dashboard</h2>
<p>Manage the catalogue using the Products and Categories pages.</p>
<table><thead><tr><th>Record type</th><th>Total</th></tr></thead><tbody>
@foreach($counts as $label => $count)<tr><td>{{ $label }}</td><td>{{ $count }}</td></tr>@endforeach
</tbody></table>
<p>Manage accounts from Users and Roles. View orders and their items from Orders. Add and edit delivery addresses from Addresses.</p>
@endsection
