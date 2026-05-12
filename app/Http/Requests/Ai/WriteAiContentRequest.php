<?php

namespace App\Http\Requests\Ai;

use App\Http\Requests\FormRequest;

class WriteAiContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Decode JSON quote_context from query parameters
        $quoteContext = $this->input('quote_context');
        if (is_string($quoteContext)) {
            $this->merge([
                'quote_context' => json_decode($quoteContext, true) ?? [],
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'block_type' => ['required', 'in:cover_message,terms,notes,payment_terms'],
            'existing_text' => ['nullable', 'string'],
            'quote_context' => ['nullable'],
            'locale' => ['nullable', 'string'],
        ];
    }
}
