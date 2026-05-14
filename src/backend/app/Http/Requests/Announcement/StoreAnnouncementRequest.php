<?php

namespace App\Http\Requests\Announcement;

use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = (string) $this->input('body', '');
            if (HtmlSanitizer::containsDangerousPatterns($body)) {
                $validator->errors()->add('body', '本文に使用できないパターンが含まれています。');
            }
        });
    }
}
