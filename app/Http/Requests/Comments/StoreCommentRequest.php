<?php

namespace App\Http\Requests\Comments;

use App\Http\Requests\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'mentions' => ['nullable', 'array'],
            'mentions.*' => ['integer'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }
}
