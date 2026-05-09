<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\FormRequest;

class InvoiceBulkActionRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:delete,archive'],
        ];
    }
}
