<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public const DEFAULT_PAYLOAD_SCHEMA_VERSION = 1;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payload', []);
        if (! is_array($payload)) {
            return;
        }
        if (! array_key_exists('schema_version', $payload)) {
            $payload['schema_version'] = self::DEFAULT_PAYLOAD_SCHEMA_VERSION;
            $this->merge(['payload' => $payload]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'payload' => ['required', 'array'],
            'payload.schema_version' => ['required', 'integer', 'min:1', 'max:99'],
            'payload.start_date' => ['required', 'date'],
            'payload.end_date' => ['required', 'date', 'after_or_equal:payload.start_date'],
            'payload.reason' => ['required', 'string', 'max:1000'],
            'payload.leave_days' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
