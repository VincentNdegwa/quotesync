<?php

namespace App\Http\Requests\Ai;

use App\Http\Requests\FormRequest;

class ImproveAiWritingRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'action' => ['required', 'in:clearer,formal,friendly,shorter,rewrite'],
            'locale' => ['nullable', 'string'],
        ];
    }
}
