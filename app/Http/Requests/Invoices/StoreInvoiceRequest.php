<?php

namespace App\Http\Requests\Invoices;

use App\Http\Requests\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'title' => ['required', 'string', 'max:255'],
            'cover_message' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:3'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.catalog_item_id' => ['nullable', 'exists:catalog_items,id'],
            'line_items.*.name' => ['required', 'string', 'max:255'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['required', 'numeric', 'min:0'],
            'line_items.*.discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'line_items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.total' => ['required', 'numeric', 'min:0'],
            'line_items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
