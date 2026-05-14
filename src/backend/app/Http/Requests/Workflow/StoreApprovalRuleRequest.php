<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'request_type' => ['required', 'string', 'max:100'],
            'scope_type' => ['nullable', Rule::in(['global', 'company', 'group'])],
            'scope_id' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'conditions' => ['nullable', 'array'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.assignee_type' => ['required', Rule::in(['user', 'role', 'group_manager'])],
            'steps.*.assignee_value' => ['required', 'string', 'max:255'],
            'steps.*.min_approvals' => ['nullable', 'integer', 'min:1'],
            'steps.*.all_must_approve' => ['nullable', 'boolean'],
            'steps.*.allow_delegate' => ['nullable', 'boolean'],
        ];
    }
}
