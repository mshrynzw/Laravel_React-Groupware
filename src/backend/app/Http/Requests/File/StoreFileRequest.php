<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
        $maxKb = (int) config('app.max_upload_kb', 10240);

        return [
            'file' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:pdf,jpeg,jpg,png,gif,txt,zip,doc,docx,xls,xlsx,ppt,pptx',
            ],
        ];
    }
}
