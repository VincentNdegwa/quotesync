<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\FormRequest;

class RefundInvoicePaymentRequest extends FormRequest
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
            'refund_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
