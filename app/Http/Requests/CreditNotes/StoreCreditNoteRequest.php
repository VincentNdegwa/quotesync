<?php

namespace App\Http\Requests\CreditNotes;

use App\Http\Requests\FormRequest;

class StoreCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:full,partial,line_items',
            'title' => 'required|string|max:255',
            'reason' => 'required|string',
            'partial_amount' => 'required_if:type,partial|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'line_items' => 'required_if:type,line_items|array',
            'line_items.*.id' => 'required|integer|exists:invoice_line_items,id',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.original_quantity' => 'required|numeric|min:0',
            'line_items.*.credit_quantity' => 'required|numeric|min:0',
        ];
    }
}
