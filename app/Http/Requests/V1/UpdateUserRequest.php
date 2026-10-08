<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'phone' => ['required', 'string', 'max:15'],
                'image_url' => ['nullable', 'string', 'url'],
            ];
        } else {
            return [
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
                'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
                'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
                'phone' => ['sometimes', 'required', 'string', 'max:15'],
                'image_url' => ['sometimes', 'nullable', 'string', 'url'],
            ];
        }
    }

    public function prepareForValidation()
    {
        $mapped = [];
        foreach (['roleId' => 'role_id', 'imageUrl' => 'image_url'] as $input => $attribute) {
            if ($this->exists($input)) {
                $mapped[$attribute] = $this->input($input);
            }
        }
        $this->merge($mapped);
    }
}
