<?php

namespace App\Http\Controllers\Api\V1;

use App\Filter\V1\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CheckoutOrderRequest;
use App\Http\Requests\V1\UpdateOrderRequest;
use App\Http\Resources\V1\OrderCollection;
use App\Http\Resources\V1\OrderResource;
use App\Mail\OrderConfirmation;
use App\Mail\OrderShippingUpdate;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = (new OrderFilter)->transform($request);
        $query = Order::with(['items.product.images', 'address', 'user.role'])->where($filters)->latest('placed_at');

        if (! $this->isStaff($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return new OrderCollection($query->paginate()->appends($request->query()));
    }

    public function store(CheckoutOrderRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $address = Address::whereKey($data['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $order = DB::transaction(function () use ($data, $user, $address) {
            $products = Product::whereKey(collect($data['items'])->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $preparedItems = collect($data['items'])->map(function ($item) use ($products) {
                $product = $products->get($item['product_id']);
                abort_unless($product, 422, 'A selected product is unavailable.');
                abort_if($product->stock_quantity < $item['quantity'], 422, "Insufficient stock for {$product->name}.");
                $price = $this->sellingPriceInCents($product);
                $product->decrement('stock_quantity', $item['quantity']);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $this->formatCents($price),
                    'quantity' => $item['quantity'],
                    'line_total' => $this->formatCents($price * $item['quantity']),
                ];
            });

            $subtotal = $preparedItems->sum(fn ($item) => $this->toCents($item['line_total']));
            $coupon = empty($data['coupon_code']) ? null : Coupon::where('code', $data['coupon_code'])
                ->where('active', true)->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
            abort_if(! empty($data['coupon_code']) && ! $coupon, 422, 'The coupon is invalid or expired.');
            $discount = $coupon ? (int) round($subtotal * $coupon->percent_off / 100) : 0;
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'status' => 'pending',
                'subtotal' => $this->formatCents($subtotal),
                'total' => $this->formatCents($subtotal - $discount),
                'discount_total' => $this->formatCents($discount),
                'coupon_code' => $coupon?->code,
                'payment_method' => $data['payment_method'] ?? 'manual',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'placed_at' => now(),
            ]);
            $order->items()->createMany($preparedItems->all());

            return $order;
        });

        $order->load(['items.product.images', 'address', 'user.role']);
        Mail::to($order->user->email)->send(new OrderConfirmation($order));

        return (new OrderResource($order))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($this->isStaff($request) || $order->user_id === $request->user()->id, 403);

        return new OrderResource($order->load(['items.product.images', 'address', 'user.role']));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->validated();
        $previousFulfillment = $order->fulfillment_status;

        $order = DB::transaction(function () use ($order, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (isset($data['fulfillment_status']) && $data['fulfillment_status'] !== $lockedOrder->fulfillment_status) {
                $allowed = [
                    'unfulfilled' => ['processing', 'cancelled'],
                    'processing' => ['shipped', 'cancelled'],
                    'shipped' => ['delivered'],
                    'delivered' => [],
                    'cancelled' => [],
                ];

                if (! in_array($data['fulfillment_status'], $allowed[$lockedOrder->fulfillment_status] ?? [], true)) {
                    throw ValidationException::withMessages([
                        'fulfillmentStatus' => 'Fulfillment must advance one step at a time and cannot move backwards.',
                    ]);
                }

                if ($data['fulfillment_status'] === 'cancelled') {
                    if (($data['payment_status'] ?? $lockedOrder->payment_status) !== 'unpaid') {
                        throw ValidationException::withMessages([
                            'fulfillmentStatus' => 'Only an unpaid order can be cancelled through fulfillment.',
                        ]);
                    }
                    $data['status'] = 'cancelled';
                    if ($lockedOrder->payment_status === 'unpaid' && $lockedOrder->inventory_restored_at === null) {
                        $lockedOrder->load('items');
                        foreach ($lockedOrder->items as $item) {
                            Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                        }
                        $data['inventory_restored_at'] = now();
                    }
                }

                if ($data['fulfillment_status'] === 'shipped') {
                    $data['shipped_at'] = now();
                }
                if ($data['fulfillment_status'] === 'delivered') {
                    $data['delivered_at'] = now();
                }
            }

            $lockedOrder->update($data);

            return $lockedOrder;
        });

        if (isset($data['fulfillment_status']) && $data['fulfillment_status'] !== $previousFulfillment && $data['fulfillment_status'] !== 'cancelled') {
            $order->load(['items', 'user']);
            Mail::to($order->user->email)->send(new OrderShippingUpdate($order));
        }

        return new OrderResource($order->load(['items.product.images', 'address', 'user.role']));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->noContent();
    }

    private function isStaff(Request $request): bool
    {
        return in_array($request->user()->role?->role_type, ['Admin', 'Employee'], true);
    }

    private function sellingPriceInCents(Product $product): int
    {
        $regular = $this->toCents($product->price);
        $offer = $this->toCents($product->offer_price);

        return $offer > 0 && $offer < $regular ? $offer : $regular;
    }

    private function toCents(string|float|int $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function formatCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
