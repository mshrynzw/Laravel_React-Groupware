<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'role' => ['sometimes', 'required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_MEMBER])],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'primary_group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }
}
