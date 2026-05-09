<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\FormRequest;

class CatalogImportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_token' => ['required', 'string'],
            'column_mapping' => ['array'],
            'unit_mapping_mode' => ['required', 'in:all,individual'],
            'unit_for_all' => ['nullable', 'string', 'required_if:unit_mapping_mode,all'],
            'unit_mapping' => ['nullable', 'array', 'required_if:unit_mapping_mode,individual'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_for_all.required_if' => 'Please select a unit when applying to all items.',
            'unit_mapping.required_if' => 'Please select units for each item when using individual mapping.',
        ];
    }
}
