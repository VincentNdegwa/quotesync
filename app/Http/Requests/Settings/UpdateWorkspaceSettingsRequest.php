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

        return [
            'settings' => ['required', 'array'],
            ...$service->rulesForGroup($group),
        ];
    }
}
