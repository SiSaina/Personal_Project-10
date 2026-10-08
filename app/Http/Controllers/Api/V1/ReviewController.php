<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Product $product)
    {
        return response()->json(['data' => $product->reviews()->with('user:id,name')->latest()->get()]);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:2000']]);
        $review = Review::updateOrCreate(['user_id' => $request->user()->id, 'product_id' => $product->id], $data);

        return response()->json(['data' => $review->load('user:id,name')], 201);
    }

    public function destroy(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id || $request->user()->role?->role_type === 'Admin', 403);
        $review->delete();

        return response()->noContent();
    }
}
