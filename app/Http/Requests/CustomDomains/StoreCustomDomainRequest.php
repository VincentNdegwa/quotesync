<?php

namespace App\Http\Requests\CustomDomains;

use App\Http\Requests\FormRequest;

class StoreCustomDomainRequest extends FormRequest
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
            'domain' => ['required', 'string', 'unique:workspace_custom_domains,domain'],
        ];
    }
}
