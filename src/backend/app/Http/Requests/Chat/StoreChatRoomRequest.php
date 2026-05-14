<?php

namespace App\Http\Requests\Chat;

use App\Models\ChatRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRoomRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in([ChatRoom::TYPE_DIRECT, ChatRoom::TYPE_GROUP])],
            'name' => ['required_if:type,'.ChatRoom::TYPE_GROUP, 'nullable', 'string', 'max:255'],
            'participant_user_id' => [
                'required_if:type,'.ChatRoom::TYPE_DIRECT,
                'prohibited_if:type,'.ChatRoom::TYPE_GROUP,
                'nullable',
                'integer',
                'exists:users,id',
                Rule::notIn([(int) $this->user()->id]),
            ],
        ];
    }
}
