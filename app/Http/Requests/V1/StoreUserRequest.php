<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'phone' => ['required', 'string', 'max:15'],
            'image_url' => ['nullable', 'string', 'url'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'role_id' => $this->input('roleId', $this->input('role_id')),
            'image_url' => $this->input('imageUrl', $this->input('image_url')),
        ]);
    }
}
