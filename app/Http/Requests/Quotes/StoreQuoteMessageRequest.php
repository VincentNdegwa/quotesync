<?php

namespace App\Http\Requests\Quotes;

use App\Http\Requests\FormRequest;

class StoreQuoteMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
