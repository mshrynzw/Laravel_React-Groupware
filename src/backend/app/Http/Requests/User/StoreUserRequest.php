<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['nullable', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_MEMBER])],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'primary_group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }
}
