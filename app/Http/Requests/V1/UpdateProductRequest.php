<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (Request()->isMethod('PUT')) {
            return [
                'category_id' => ['required', 'integer', 'exists:categories,id'],
                'date' => ['required', 'date'],
                'description' => ['required', 'string', 'max:1000'],
                'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($this->product)],
                'offer_price' => ['required', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock_quantity' => ['required', 'integer', 'min:0'],
            ];
        } else {
            return [
                'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
                'date' => ['sometimes', 'required', 'date'],
                'description' => ['sometimes', 'required', 'string', 'max:1000'],
                'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($this->product)],
                'offer_price' => ['sometimes', 'required', 'numeric', 'min:0'],
                'price' => ['sometimes', 'required', 'numeric', 'min:0'],
                'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            ];
        }
    }

    public function prepareForValidation()
    {
        $mapped = [];
        foreach (['categoryId' => 'category_id', 'offerPrice' => 'offer_price', 'stockQuantity' => 'stock_quantity'] as $input => $attribute) {
            if ($this->exists($input)) {
                $mapped[$attribute] = $this->input($input);
            }
        }
        $this->merge($mapped);
    }
}
