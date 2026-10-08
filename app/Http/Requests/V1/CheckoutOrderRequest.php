<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'payment_method' => ['sometimes', 'string', 'in:manual,bank_transfer,cash_on_delivery'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (is_array($item) && array_key_exists('productId', $item)) {
                    $item['product_id'] = $item['productId'];
                }

                return $item;
            })->all();

        $mapped = ['items' => $items];
        if ($this->exists('addressId')) {
            $mapped['address_id'] = $this->input('addressId');
        }
        if ($this->exists('couponCode')) {
            $mapped['coupon_code'] = strtoupper(trim($this->input('couponCode')));
        }
        if ($this->exists('paymentMethod')) {
            $mapped['payment_method'] = $this->input('paymentMethod');
        }
        $this->merge($mapped);
    }
}
