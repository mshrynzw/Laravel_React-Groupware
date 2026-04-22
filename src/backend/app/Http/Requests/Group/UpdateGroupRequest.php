<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Group $group */
        $group = $this->route('group');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('groups', 'name')->ignore($group->id)],
            'description' => ['nullable', 'string'],
        ];
    }
}
