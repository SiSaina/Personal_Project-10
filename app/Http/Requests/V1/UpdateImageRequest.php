<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImageRequest extends FormRequest
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
                'url' => ['required', 'string', 'max:255'],
                'product_id' => ['required', 'integer', 'exists:products,id'],
            ];
        } else {
            return [
                'url' => ['sometimes', 'required', 'string', 'max:255'],
                'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            ];
        }
    }

    public function prepareForValidation()
    {
        if ($this->exists('productId')) {
            $this->merge(['product_id' => $this->input('productId')]);
        }
    }
}
