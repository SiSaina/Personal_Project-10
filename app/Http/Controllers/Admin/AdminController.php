<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginForm()
    {
        if (Auth::guard('web')->user()?->role?->role_type === 'Admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->role?->role_type !== 'Admin') {
            throw ValidationException::withMessages(['email' => 'Invalid credentials or this account is not an administrator.']);
        }
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        return view('admin.dashboard', ['counts' => [
            'Products' => Product::count(), 'Categories' => Category::count(),
            'Users' => User::count(), 'Orders' => Order::count(), 'Addresses' => Address::count(),
        ]]);
    }

    public function products(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $products = Product::with('category')->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        return view('admin.products', compact('products', 'search'));
    }

    public function productForm(?Product $product = null)
    {
        return view('admin.product-form', ['product' => $product ?? new Product(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function saveProduct(Request $request, ?Product $product = null)
    {
        $product ??= new Product();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'required|string|max:255', 'date' => 'required|date',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'offer_price' => 'required|numeric|min:0|lte:price|max:99999999.99',
        ]);
        $product->fill($data)->save();
        return redirect()->route('admin.products')->with('status', 'Product saved.');
    }

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('name')->paginate(15)]);
    }

    public function orders(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $orders = Order::with('user')->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                if (ctype_digit($search)) {
                    $query->where('id', $search)->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                } else {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                }
            }))
            ->latest('placed_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.orders', compact('orders', 'search'));
    }

    public function orderDetails(Order $order)
    {
        return view('admin.order-details', ['order' => $order->load(['user', 'address', 'items.product'])]);
    }

    public function addresses(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $addresses = Address::with('user')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('full_name', 'like', '%'.$search.'%')->orWhere('city', 'like', '%'.$search.'%')
                ->orWhere('street_name', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($user) => $user->where('email', 'like', '%'.$search.'%'))))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.addresses', compact('addresses', 'search'));
    }

    public function addressForm(?Address $address = null)
    {
        return view('admin.address-form', [
            'address' => $address ?? new Address(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function saveAddress(Request $request, ?Address $address = null)
    {
        $address ??= new Address();
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'full_name' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'street_name' => 'required|string|max:255',
            'suburb' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);
        if ($address->exists && (int) $address->user_id !== (int) $data['user_id']
            && Order::where('address_id', $address->id)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'An address used by an order cannot be moved to another user.']);
        }
        $address->fill($data)->save();

        return redirect()->route('admin.addresses')->with('status', 'Address saved.');
    }

    public function users(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'role' => 'nullable|integer|exists:roles,id']);
        $search = $filters['search'] ?? '';
        $roleId = $filters['role'] ?? null;
        $users = User::with('role')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when($roleId, fn ($query) => $query->where('role_id', $roleId))
            ->orderBy('id')->paginate(15)->withQueryString();
        return view('admin.users', ['users' => $users, 'search' => $search, 'roleId' => $roleId, 'roles' => Role::orderBy('id')->get()]);
    }

    public function userForm(?User $user = null)
    {
        return view('admin.user-form', ['user' => $user ?? new User(), 'roles' => Role::orderBy('id')->get()->filter(fn ($role) => in_array($role->role_type, ['Admin', 'Employee', 'Customer'], true))]);
    }

    public function saveUser(Request $request, ?User $user = null)
    {
        $user ??= new User();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:15',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
        $role = Role::findOrFail($data['role_id']);
        if (! in_array($role->role_type, ['Admin', 'Employee', 'Customer'], true)) {
            throw ValidationException::withMessages(['role_id' => 'Choose Admin, Employee, or Customer.']);
        }
        if ($user->exists && $user->role?->role_type === 'Admin' && $role->role_type !== 'Admin') {
            if ($user->id === Auth::guard('web')->id()) {
                throw ValidationException::withMessages(['role_id' => 'You cannot remove your own administrator access.']);
            }
            if (User::whereHas('role', fn ($query) => $query->whereRaw('LOWER(TRIM(role_type)) = ?', ['admin']))->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'The last administrator must keep administrator access.']);
            }
        }
        $passwordChanged = ! empty($data['password']);
        if (! $passwordChanged) {
            unset($data['password']);
        }
        $emailChanged = $user->exists && $user->email !== $data['email'];
        $roleChanged = $user->exists && $user->role_id !== (int) $data['role_id'];
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data, $passwordChanged, $emailChanged, $roleChanged) {
            $user->fill($data);
            if ($emailChanged) {
                $user->email_verified_at = null;
            }
            $user->save();
            if ($passwordChanged || $emailChanged || $roleChanged) {
                $user->tokens()->delete();
            }
        });
        return redirect()->route('admin.users')->with('status', 'User saved.');
    }

    public function roles()
    {
        return view('admin.roles', ['roles' => Role::withCount('users')->orderBy('id')->get()]);
    }

    public function ensureRoles()
    {
        foreach (['Admin', 'Employee', 'Customer'] as $name) {
            if (! Role::whereRaw('LOWER(TRIM(role_type)) = ?', [strtolower($name)])->exists()) {
                Role::create(['role_type' => $name]);
            }
        }
        return redirect()->route('admin.roles')->with('status', 'The three supported roles are available.');
    }

    public function saveCategory(Request $request, ?Category $category = null)
    {
        $category ??= new Category();
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)]]);
        $category->fill($data)->save();
        return redirect()->route('admin.categories')->with('status', 'Category saved.');
    }
}
