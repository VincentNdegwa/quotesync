<?php

namespace App\Http\Requests\Settings;

use App\Models\Role;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteWorkspaceOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $user?->currentWorkspace;

        if (! $user || ! $workspace instanceof Workspace) {
            return false;
        }

        return $user->belongsToWorkspace($workspace)
            && ($workspace->owner_id === $user->id || $user->hasRole('admin', $workspace));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Workspace|null $workspace */
        $workspace = $this->user()?->currentWorkspace;

        $stepIndex = max(1, min((int) $this->input('step_index', 1), 3));

        $rules = [
            'step_index' => ['required', 'integer', 'min:1', 'max:3'],
            'navigation' => ['nullable', Rule::in(['next', 'finish'])],
        ];

        if ($stepIndex === 1) {
            return [
                ...$rules,
                'company_name' => ['required', 'string', 'max:255'],
                'country' => ['required', 'string', 'size:2'],
                'logo_path' => ['nullable', 'string', 'max:512'],
            ];
        }

        if ($stepIndex === 2) {
            return [
                ...$rules,
                'currency' => ['required', 'string', 'size:3'],
                'quote_prefix' => ['required', 'string', 'max:20'],
            ];
        }

        return [
            ...$rules,
            'invites' => ['nullable', 'array', 'max:3'],
            'invites.*.email' => ['nullable', 'email', 'max:255'],
            'invites.*.role_id' => [
                'nullable',
                'required_with:invites.*.email',
                Rule::exists((new Role)->getTable(), 'id')
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $workspace?->id)
                        ->orWhereNull('workspace_id')),
            ],
        ];
    }
}
