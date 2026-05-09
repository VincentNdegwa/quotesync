<?php

namespace App\Http\Requests\Ai;

use App\Http\Requests\FormRequest;

class GenerateAiTemplateRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:2000'],
            'industry' => ['nullable', 'string', 'max:100'],
        ];
    }
}
