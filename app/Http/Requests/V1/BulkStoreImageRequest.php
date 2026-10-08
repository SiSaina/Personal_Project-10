<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreImageRequest extends FormRequest
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
        return [
            '*.url' => ['required', 'string', 'max:255'],
            '*.product_id' => ['required', 'integer', 'exists:products,id'],
        ];
    }

    public function prepareForValidation()
    {
        $data = [];

        foreach ($this->all() as $obj) {
            if (array_key_exists('productId', $obj)) {
                $obj['product_id'] = $obj['productId'];
            }
            $data[] = $obj;
        }
        $this->replace($data);
    }
}
