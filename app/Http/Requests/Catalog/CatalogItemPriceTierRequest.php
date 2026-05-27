<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\FormRequest;

class CatalogItemPriceTierRequest extends FormRequest
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
            'min_quantity' => ['required', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'pricing_type' => ['required', 'in:fixed_price,discount_percent'],
            'value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
