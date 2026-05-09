<?php

namespace App\Http\Requests\PublicPortal;

use App\Http\Requests\FormRequest;

class DeclineQuoteRequest extends FormRequest
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
            'decline_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
