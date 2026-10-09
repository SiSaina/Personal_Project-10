<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Store administration')</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 32px auto; padding: 0 20px; color: #222; }
        nav { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; padding-bottom: 16px; border-bottom: 1px solid #ccc; }
        nav form { margin-left: auto; } a { color: #164e99; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        label { display: block; margin: 16px 0 6px; }
        input, select, textarea, button { font: inherit; padding: 8px; box-sizing: border-box; }
        .editor { max-width: 550px; } .editor input, .editor select, .editor textarea { width: 100%; }
        button { cursor: pointer; } .notice { padding: 12px; background: #eef7ee; }
        .errors { padding: 12px; background: #fff0f0; } .actions { margin-top: 20px; }
        .table-wrap { overflow-x: auto; } .inline { display: flex; gap: 8px; align-items: center; }
    </style>
</head>
<body>
    <h1>Store administration</h1>
    @if(auth('web')->user()?->role?->role_type === 'Admin')
        <nav aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.products') }}">Products</a>
            <a href="{{ route('admin.categories') }}">Categories</a>
            <a href="{{ route('admin.orders') }}">Orders</a>
            <a href="{{ route('admin.addresses') }}">Addresses</a>
            <a href="{{ route('admin.users') }}">Users</a>
            <a href="{{ route('admin.roles') }}">Roles</a>
            <span>{{ auth('web')->user()->name }}</span>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button>Log out</button></form>
        </nav>
    @endif
    @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())
        <div class="errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <main>@yield('content')</main>
</body>
</html>
