<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'status' => ['sometimes', 'string', Rule::in(Task::STATUSES)],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'assignee_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
