<?php

namespace App\Http\Requests\PublicPortal;

use App\Http\Requests\FormRequest;

class AcceptQuoteRequest extends FormRequest
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
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ];
    }
}
