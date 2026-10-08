<?php

namespace App\Http\Controllers\Api\V1;

use App\Filter\V1\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProductRequest;
use App\Http\Requests\V1\UpdateProductRequest;
use App\Http\Resources\V1\ProductCollection;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = new ProductFilter;
        $filterItems = $filter->transform($request);

        $includeImages = $request->query('includeImages');
        $includeCategory = $request->query('includeCategory');

        $products = Product::where($filterItems);
        if ($search = trim((string) $request->query('search'))) {
            $products->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        $products->withAvg('reviews', 'rating');
        if ($includeImages) {
            $products->with('images');
        }
        if ($includeCategory) {
            $products->with('category');
        }

        return new ProductCollection($products
            ->orderBy('id', 'asc')
            ->paginate()
            ->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        return new ProductResource(Product::create($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $includeImages = request()->query('includeImages');
        $includeCategory = request()->query('includeCategory');
        if ($includeImages) {
            $product->loadMissing('images');
        }
        if ($includeCategory) {
            $product->loadMissing('category');
        }

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }
}
