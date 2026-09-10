<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        return ProductResource::collection(Product::with('images')->whereIn('id', DB::table('wishlists')->where('user_id', $request->user()->id)->pluck('product_id'))->get());
    }

    public function store(Request $request, Product $product)
    {
        DB::table('wishlists')->updateOrInsert(['user_id' => $request->user()->id, 'product_id' => $product->id], ['created_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Added to wishlist.'], 201);
    }

    public function destroy(Request $request, Product $product)
    {
        DB::table('wishlists')->where(['user_id' => $request->user()->id, 'product_id' => $product->id])->delete();

        return response()->noContent();
    }
}
