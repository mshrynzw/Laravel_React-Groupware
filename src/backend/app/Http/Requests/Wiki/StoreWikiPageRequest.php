<?php

namespace App\Http\Requests\Wiki;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWikiPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('wiki_pages', 'slug'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:500000'],
            'parent_id' => ['nullable', 'integer', 'exists:wiki_pages,id'],
        ];
    }
}
