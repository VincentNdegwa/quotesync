<?php

namespace App\Http\Requests\Approvals;

use App\Http\Requests\FormRequest;

class UpdateApprovalRuleRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }
}
