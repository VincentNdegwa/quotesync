<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\FormRequest;
use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Validation\Rule;

class UpdateWorkspaceSettingsRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var WorkspaceSettingsService $service */
        $service = app(WorkspaceSettingsService::class);
        $group = (string) $this->route('group');

        $groups = array_keys($service->groups());

        if (! in_array($group, $groups, true)) {
            return ['group' => [Rule::in($groups)]];
        }

        if ($group === 'brand') {
            return [
                'company_name' => ['required', 'string', 'max:255'],
                'country' => ['required', 'string', 'max:2'],
                'currency' => ['required', 'string', 'max:3'],
                'primary_color' => ['nullable', 'string', 'max:7'],
                'accent_color' => ['nullable', 'string', 'max:7'],
                'address' => ['nullable', 'string'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'tax_number' => ['nullable', 'string', 'max:50'],
                'logo_path' => ['nullable', 'file', 'image', 'max:5120'],
                'favicon_path' => ['nullable', 'file', 'mimes:ico,png', 'max:5120'],
                'white_label_mode' => ['nullable', 'boolean'],
                'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            ];
        }

        $rules = [
            'settings' => ['required', 'array'],
            ...$service->rulesForGroup($group),
        ];

        return $rules;
    }
}
