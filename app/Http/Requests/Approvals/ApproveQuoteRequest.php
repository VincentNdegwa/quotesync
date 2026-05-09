<?php

namespace App\Http\Requests\Approvals;

use App\Http\Requests\FormRequest;

class ApproveQuoteRequest extends FormRequest
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
            'comment' => ['nullable', 'string', 'max:1000'],
            'send' => ['nullable', 'boolean'],
        ];
    }
}
