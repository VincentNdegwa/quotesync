<?php

namespace App\Http\Requests\Clients;

use App\Http\Requests\FormRequest;

class ClientImportStoreRequest extends FormRequest
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
            'import_token' => ['required', 'string'],
            'column_mapping' => ['array'],
        ];
    }
}
