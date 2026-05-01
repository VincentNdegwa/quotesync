<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWorkspaceSettingsRequest;
use App\Models\Industry;
use App\Models\Workspace;
use App\Services\FileStorageService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $componentMap = [
            'brand' => 'settings/setup/brand',
            'quotes_invoices' => 'settings/setup/quotes_invoices',
            'email' => 'settings/setup/email',
            'notifications' => 'settings/setup/notifications',
            'localization' => 'settings/setup/localization',
        ];

        $component = $componentMap[$defaultGroup] ?? 'settings/WorkspaceSettings';

        $props = [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
                'settings_onboarded_at' => $workspace->settings_onboarded_at?->toIso8601String(),
                'industry_id' => $workspace->industry_id,
            ],
            'groups' => $groups,
            'industries' => Industry::query()
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'icon', 'color'])
                ->map(fn (Industry $industry): array => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'icon' => $industry->icon,
                    'color' => $industry->color,
                ])
                ->values(),
        ];

        if ($defaultGroup === 'brand') {
            $props['business'] = [
                'company_name' => $workspace->name,
                'country' => $workspace->country,
                'logo_path' => $workspace->logo_url,
                'currency' => $workspace->currency,
                'primary_color' => $workspace->primary_color,
                'accent_color' => $workspace->accent_color,
                'address' => $workspace->address,
                'phone' => $workspace->phone,
                'email' => $workspace->email,
                'website' => $workspace->website,
                'tax_number' => $workspace->tax_number,
                'favicon_path' => $workspace->favicon_url,
                'white_label_mode' => $workspace->white_label_mode,
                'industry_id' => $workspace->industry_id
            ];
        } elseif ($defaultGroup === 'quotes_invoices') {
            $settings = $settingsService->groupForFrontend($workspace, $defaultGroup);
            $allFields = [];
            
            if (isset($settings['subsections'])) {
                foreach ($settings['subsections'] as $subsection) {
                    $allFields = array_merge($allFields, $subsection['fields']);
                }
            }
            
            $settings['fields'] = $allFields;
            $props['currentGroup'] = $settings;
        } else {
            $settings = $settingsService->groupForFrontend($workspace, $defaultGroup);
            $props['currentGroup'] = $settings;

            if ($defaultGroup === 'localization') {
                $props['timezones'] = \DateTimeZone::listIdentifiers();
            }
        }

        return Inertia::render($component, $props);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, WorkspaceSettingsService $settingsService, FileStorageService $fileStorageService, string $group): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        if (isset($validated['industry_id'])) {
            $workspace->update(['industry_id' => $validated['industry_id']]);
        }

        if ($group === 'brand') {
            $workspace->update([
                'name' => $validated['company_name'] ?? $workspace->name,
                'country' => $validated['country'] ?? $workspace->country,
                'currency' => $validated['currency'] ?? $workspace->currency,
                'primary_color' => $validated['primary_color'] ?? $workspace->primary_color,
                'accent_color' => $validated['accent_color'] ?? $workspace->accent_color,
                'address' => $validated['address'] ?? $workspace->address,
                'phone' => $validated['phone'] ?? $workspace->phone,
                'email' => $validated['email'] ?? $workspace->email,
                'website' => $validated['website'] ?? $workspace->website,
                'tax_number' => $validated['tax_number'] ?? $workspace->tax_number,
                'white_label_mode' => $validated['white_label_mode'] ?? $workspace->white_label_mode,
            ]);

            if ($request->hasFile('logo_path')) {
                $result = $fileStorageService->store($request->file('logo_path'), "workspaces/{$workspace->id}/branding");
                if (!$result['error']) {
                    $workspace->update(['logo_url' => $result['url']]);
                }
            }

            if ($request->hasFile('favicon_path')) {
                $result = $fileStorageService->store($request->file('favicon_path'), "workspaces/{$workspace->id}/branding");
                if (!$result['error']) {
                    $workspace->update(['favicon_url' => $result['url']]);
                }
            }
        } elseif ($group === 'quotes_invoices') {
            $settingsPayload = $validated['settings'];
            $definition = $settingsService->groupDefinition($group);
            $subsections = $definition['subsections'] ?? [];

            foreach ($subsections as $subsectionKey => $subsection) {
                $subsectionFields = $subsection['fields'] ?? [];
                $subsectionPayload = [];

                foreach ($subsectionFields as $key => $field) {
                    if (isset($settingsPayload[$key])) {
                        $subsectionPayload[$key] = $settingsPayload[$key];
                    }
                }

                if (! empty($subsectionPayload)) {
                    $settingsService->updateGroup(
                        $workspace,
                        $subsectionKey,
                        $subsectionPayload,
                    );
                }
            }
        } else {
            $settingsPayload = $validated['settings'];

            if ($request->hasFile('settings.logo_path')) {
                $result = $fileStorageService->store($request->file('settings.logo_path'), "workspaces/{$workspace->id}/branding");
                if (!$result['error']) {
                    $settingsPayload['logo_path'] = $result['url'];
                }
            }

            $settingsService->updateGroup(
                $workspace,
                $group,
                $settingsPayload,
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business setup updated.')]);

        return back();
    }
}
