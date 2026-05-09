<?php

namespace App\Http\Requests\Ai;

use App\Http\Requests\FormRequest;

class GenerateAiQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
