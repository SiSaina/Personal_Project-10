@extends('admin.layout')
@section('title', 'Admin login')
@section('content')
<h2>Admin login</h2>
<p>Use your administrator account. Employee and customer accounts cannot access this panel.</p>
<form class="editor" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
    <label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required>
    <div class="actions"><button>Log in</button></div>
</form>
@endsection
