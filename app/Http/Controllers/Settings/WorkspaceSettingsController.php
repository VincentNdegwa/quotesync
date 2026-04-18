<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWorkspaceSettingsRequest;
use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function show(Request $request, WorkspaceSettingsService $settingsService, ?string $group = null): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $defaultGroup = $group ?? 'brand';
        $canManageHiddenGroups = $request->user()?->hasRole('admin', $workspace) || $workspace->owner_id === $request->user()?->id;
        $groups = $settingsService->frontendGroups(includeHidden: false);

        $allGroupKeys = array_keys($settingsService->groups());

        abort_unless(in_array($defaultGroup, $allGroupKeys, true), 404);

        if (! $settingsService->isGroupVisible($defaultGroup) && ! $canManageHiddenGroups) {
            abort(404);
        }

        $settings = $settingsService->groupForFrontend($workspace, $defaultGroup);

        return Inertia::render('settings/WorkspaceSettings', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
                'settings_onboarded_at' => $workspace->settings_onboarded_at?->toIso8601String(),
            ],
            'groups' => $groups,
            'currentGroup' => $settings,
        ]);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, WorkspaceSettingsService $settingsService, string $group): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        $settingsService->updateGroup(
            $workspace,
            $group,
            $validated['settings'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business setup updated.')]);

        return back();
    }
}
