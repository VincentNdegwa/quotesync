<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\FormRequest;

class CatalogItemBulkActionRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:activate,deactivate,delete,change_category'],
            'category_id' => ['nullable', 'integer'],
        ];
    }
}
