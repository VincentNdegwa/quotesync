<?php

namespace App\Http\Requests\CreditNotes;

use App\Http\Requests\FormRequest;

class VoidCreditNoteRequest extends FormRequest
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
            'void_reason' => ['required', 'string'],
        ];
    }
}
