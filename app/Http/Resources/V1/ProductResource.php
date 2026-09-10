<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'offerPrice' => $this->offer_price,
            'date' => $this->date,
            'stockQuantity' => $this->stock_quantity,
            'averageRating' => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'category' => $this->whenLoaded('category'),
        ];
    }
}
