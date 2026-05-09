<?php

namespace App\Http\Requests\Approvals;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'trigger_type' => ['required', 'in:value_above,value_below,client,all_quotes'],
            'threshold_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn (): bool => in_array($this->input('trigger_type'), ['value_above', 'value_below'], true)),
            ],
            'client_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('trigger_type') === 'client'),
                'exists:clients,id',
            ],
            'approver_id' => ['required', 'exists:users,id'],
        ];
    }
}
