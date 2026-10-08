<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role?->role_type, ['Admin', 'Employee'], true);
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['sometimes', Rule::in(['unpaid', 'pending', 'paid', 'failed', 'refunded'])],
            'fulfillment_status' => ['sometimes', Rule::in(['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('paymentStatus')) {
            $this->merge(['payment_status' => $this->input('paymentStatus')]);
        }
        if ($this->exists('fulfillmentStatus')) {
            $this->merge(['fulfillment_status' => $this->input('fulfillmentStatus')]);
        }
    }
}
