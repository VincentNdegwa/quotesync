<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompleteWorkspaceOnboardingRequest;
use App\Http\Requests\Settings\UpdateWorkspaceSettingsRequest;
use App\Models\Industry;
use App\Models\Role;
use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class WorkspaceOnboardingController extends Controller
{
    public function show(Request $request, WorkspaceSettingsService $settingsService): InertiaResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $settingsService->initializeWorkspace($workspace);

        if ($workspace->settings_onboarded_at !== null) {
            return to_route('dashboard');
        }

        $brand = $settingsService->groupForFrontend($workspace, 'brand');
        $quotes = $settingsService->groupForFrontend($workspace, 'quotes');
        $invoices = $settingsService->groupForFrontend($workspace, 'invoices');

        $quoteFields = collect($quotes['fields'])->keyBy('key');
        $invoiceFields = collect($invoices['fields'])->keyBy('key');

        $availableRoles = Role::query()
            ->where(function ($query) use ($workspace) {
                $query->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspace->id);
            })
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name', 'display_name']);

        $memberRole = $availableRoles->firstWhere('name', 'member');
        $fallbackRole = $availableRoles->first();
        $defaultRoleId = $memberRole['id'] ?? $fallbackRole['id'] ?? null;
        $currentStepIndex = max(1, min((int) $request->integer('step', 1), 2));

        return Inertia::render('onboarding/Index', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
                'industry_id' => $workspace->industry_id,
            ],
            'currentStepIndex' => $currentStepIndex,
            'business' => [
                'company_name' => $workspace->name,
                'country' => $workspace->country,
                'logo_path' => $workspace->logo_path,
                'currency' => $workspace->currency,
            ],
            'quoteDefaults' => [
                'quote_prefix' => $quoteFields->get('quote_prefix')['value'] ?? null,
                'invoice_prefix' => $quoteFields->get('invoice_prefix')['value'] ?? null,
            ],
            'availableRoles' => $availableRoles,
            'defaultRoleId' => $defaultRoleId,
            'industries' => Industry::query()
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'icon', 'color']),
        ]);
    }

    public function complete(
        CompleteWorkspaceOnboardingRequest $request,
        WorkspaceSettingsService $settingsService,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();
        $stepIndex = max(1, min((int) ($validated['step_index'] ?? 1), 2));
        $navigation = (string) ($validated['navigation'] ?? 'next');

        if ($stepIndex === 1) {
            $workspace->update([
                'industry_id' => $validated['industry_id'] ?? null,
                'name' => $validated['company_name'] ?? $workspace->name,
                'logo_path' => $validated['logo_path'] ?? null,
                'country' => $validated['country'] ?? null,
                'currency' => $validated['currency'] ?? 'USD',
            ]);
        }

        if ($stepIndex === 2) {
            $settingsService->updateGroup(
                $workspace,
                'quotes',
                [
                    'quote_prefix' => $validated['quote_prefix'],
                ],
                markOnboardingComplete: false,
            );

            $settingsService->updateGroup(
                $workspace,
                'invoices',
                [
                    'invoice_prefix' => $validated['invoice_prefix'] ?? 'INV',
                ],
                markOnboardingComplete: false,
            );

            if (isset($validated['timezone'])) {
                $settingsService->updateGroup(
                    $workspace,
                    'localization',
                    [
                        'timezone' => $validated['timezone'],
                    ],
                    markOnboardingComplete: false,
                );
            }

            if ($settingsService->isOnboardingComplete($workspace)) {
                $workspace->forceFill(['settings_onboarded_at' => now()])->save();

                Inertia::flash('toast', ['type' => 'success', 'message' => __('Business setup complete.')]);

                return to_route('dashboard');
            }

            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Please complete required setup fields before finishing.')]);

            return to_route('business-setup.onboarding', ['step' => 1]);
        }

        $nextStep = min($stepIndex + 1, 2);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step saved.')]);

        return to_route('business-setup.onboarding', [
            'step' => $navigation === 'next' ? $nextStep : $stepIndex,
        ]);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, WorkspaceSettingsService $settingsService, string $group): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        abort_unless(in_array($group, $settingsService->onboardingGroups(), true), 404);

        $validated = $request->validated();

        $settingsService->updateGroup($workspace, $group, $validated['settings']);

        if ($settingsService->isOnboardingComplete($workspace)) {
            $workspace->forceFill(['settings_onboarded_at' => now()])->save();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Business setup complete.')]);

            return to_route('dashboard');
        }

        $nextGroup = $settingsService->firstIncompleteOnboardingGroup($workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Step saved. Continue setup.')]);

        return to_route('business-setup.onboarding', [
            'group' => $nextGroup,
        ]);
    }
}
