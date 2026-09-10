<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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
                'role_type' => ['required', 'string', 'max:255'],
            ];
        } else {
            return [
                'role_type' => ['sometimes', 'required', 'string', 'max:255'],
            ];
        }
    }

    public function prepareForValidation()
    {
        if ($this->exists('roleType')) {
            $this->merge(['role_type' => $this->input('roleType')]);
        }
    }
}
