<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'scope_type' => ['sometimes', 'nullable', Rule::in(['global', 'company', 'group'])],
            'scope_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'conditions' => ['sometimes', 'array'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'steps.*.step_order' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.assignee_type' => ['required_with:steps', Rule::in(['user', 'role', 'group_manager'])],
            'steps.*.assignee_value' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.min_approvals' => ['nullable', 'integer', 'min:1'],
            'steps.*.all_must_approve' => ['nullable', 'boolean'],
            'steps.*.allow_delegate' => ['nullable', 'boolean'],
        ];
    }
}
