<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'userId' => $this->user_id,
            'addressId' => $this->address_id,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'discountTotal' => $this->discount_total,
            'couponCode' => $this->coupon_code,
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,
            'fulfillmentStatus' => $this->fulfillment_status,
            'placedAt' => $this->placed_at?->toISOString(),
            'shippedAt' => $this->shipped_at?->toISOString(),
            'deliveredAt' => $this->delivered_at?->toISOString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'address' => new AddressResource($this->whenLoaded('address')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
