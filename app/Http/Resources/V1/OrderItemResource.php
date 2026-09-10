<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'productName' => $this->product_name,
            'unitPrice' => $this->unit_price,
            'quantity' => $this->quantity,
            'lineTotal' => $this->line_total,
            'imageUrl' => $this->whenLoaded('product', fn () => $this->product?->images?->first()?->url),
        ];
    }
}
