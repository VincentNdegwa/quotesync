<?php

namespace App\Http\Requests\Configuration;

use App\Enums\FollowUpChannel;
use App\Http\Requests\FormRequest;
use App\Services\Quotes\QuotePlaceholderService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFollowUpSequenceRequest extends FormRequest
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
        $workspaceId = $this->user()?->currentWorkspace?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('follow_up_sequences', 'name')->where('workspace_id', $workspaceId),
            ],
            'is_default' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.day_offset' => ['required', 'integer', 'min:0'],
            'steps.*.channel' => ['required', 'string', Rule::in(FollowUpChannel::values())],
            'steps.*.subject' => ['nullable', 'string', 'max:255'],
            'steps.*.message_template' => ['required', 'string', 'max:5000'],
            'steps.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $steps = $this->input('steps', []);
            foreach ($steps as $index => $step) {
                if (! isset($step['message_template'])) {
                    continue;
                }

                $validation = QuotePlaceholderService::validatePlaceholders($step['message_template']);
                if (! $validation['valid']) {
                    $validator->errors()->add(
                        "steps.{$index}.message_template",
                        'Invalid placeholders: '.implode(', ', $validation['invalid']).'. Allowed: '.implode(', ', array_keys(QuotePlaceholderService::getAvailablePlaceholders()))
                    );
                }
            }
        });
    }
}
