<?php

namespace App\Http\Requests\Wiki;

use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWikiPageRequest extends FormRequest
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
        /** @var WikiPage $page */
        $page = $this->route('wiki_page');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:500000'],
            'slug' => [
                'sometimes',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('wiki_pages', 'slug')->ignore($page->id),
            ],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:wiki_pages,id', 'not_in:'.$page->id],
            'updated_at' => ['required', 'date'],
        ];
    }
}
