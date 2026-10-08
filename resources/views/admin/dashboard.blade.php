@extends('admin.layout')
@section('content')
<h2>Dashboard</h2>
<p>Manage the catalogue using the Products and Categories pages.</p>
<table><thead><tr><th>Record type</th><th>Total</th></tr></thead><tbody>
@foreach($counts as $label => $count)<tr><td>{{ $label }}</td><td>{{ $count }}</td></tr>@endforeach
</tbody></table>
<p>Manage accounts and role assignments from Users and Roles. Order management will be added in a later step.</p>
@endsection
