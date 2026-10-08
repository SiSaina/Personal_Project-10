<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'fullName' => $this->full_name,
            'postalCode' => $this->postal_code,
            'streetName' => $this->street_name,
            'suburb' => $this->suburb,
            'city' => $this->city,
            'country' => $this->country,
            'userId' => $this->user_id,
        ];
    }
}
