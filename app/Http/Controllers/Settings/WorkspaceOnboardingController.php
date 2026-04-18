<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompleteWorkspaceOnboardingRequest;
use App\Http\Requests\Settings\UpdateWorkspaceSettingsRequest;
use App\Models\Role;
use App\Models\Workspace;
use App\Services\Invitations\InvitationService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceOnboardingController extends Controller
{
    public function show(Request $request, WorkspaceSettingsService $settingsService): Response|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $settingsService->initializeWorkspace($workspace);

        if ($workspace->settings_onboarded_at !== null) {
            return to_route('dashboard');
        }

        $brand = $settingsService->groupForFrontend($workspace, 'brand');
        $localization = $settingsService->groupForFrontend($workspace, 'localization');
        $quotes = $settingsService->groupForFrontend($workspace, 'quotes');

        $brandFields = collect($brand['fields'])->keyBy('key');
        $localizationFields = collect($localization['fields'])->keyBy('key');
        $quoteFields = collect($quotes['fields'])->keyBy('key');

        $availableRoles = Role::query()
            ->where(function ($query) use ($workspace) {
                $query->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspace->id);
            })
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name', 'display_name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ])
            ->values();

        $memberRole = $availableRoles->firstWhere('name', 'member');
        $fallbackRole = $availableRoles->first();
        $defaultRoleId = $memberRole['id'] ?? $fallbackRole['id'] ?? null;
        $currentStepIndex = max(1, min((int) $request->integer('step', 1), 3));

        return Inertia::render('onboarding/Index', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
            ],
            'currentStepIndex' => $currentStepIndex,
            'business' => [
                'company_name' => $brandFields->get('company_name')['value'] ?? null,
                'country' => $localizationFields->get('country')['value'] ?? null,
                'logo_path' => $brandFields->get('logo_path')['value'] ?? null,
            ],
            'quoteDefaults' => [
                'currency' => $quoteFields->get('default_currency')['value'] ?? null,
                'quote_prefix' => $quoteFields->get('quote_prefix')['value'] ?? null,
            ],
            'availableRoles' => $availableRoles,
            'defaultRoleId' => $defaultRoleId,
        ]);
    }

    public function complete(
        CompleteWorkspaceOnboardingRequest $request,
        WorkspaceSettingsService $settingsService,
        InvitationService $invitationService,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();
        $stepIndex = max(1, min((int) ($validated['step_index'] ?? 1), 3));
        $navigation = (string) ($validated['navigation'] ?? 'next');

        if ($stepIndex === 1) {
            $settingsService->updateGroup(
                $workspace,
                'brand',
                [
                    'company_name' => $validated['company_name'],
                    'logo_path' => $validated['logo_path'] ?? null,
                ],
                markOnboardingComplete: false,
            );

            $settingsService->updateGroup(
                $workspace,
                'localization',
                [
                    'country' => $validated['country'],
                ],
                markOnboardingComplete: false,
            );
        }

        if ($stepIndex === 2) {
            $settingsService->updateGroup(
                $workspace,
                'localization',
                [
                    'currency' => $validated['currency'],
                ],
                markOnboardingComplete: false,
            );

            $settingsService->updateGroup(
                $workspace,
                'quotes',
                [
                    'quote_prefix' => $validated['quote_prefix'],
                    'default_currency' => $validated['currency'],
                ],
                markOnboardingComplete: false,
            );
        }

        if ($stepIndex === 3) {
            $invites = collect($validated['invites'] ?? [])
                ->filter(fn (array $invite): bool => trim((string) ($invite['email'] ?? '')) !== '')
                ->map(fn (array $invite): array => [
                    'email' => strtolower(trim((string) $invite['email'])),
                    'role_id' => (int) ($invite['role_id'] ?? 0),
                ])
                ->unique('email')
                ->values();

            foreach ($invites as $index => $invite) {
                try {
                    $invitationService->create(
                        $workspace,
                        $request->user(),
                        $invite['email'],
                        $invite['role_id'],
                    );
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages([
                        "invites.{$index}.email" => $exception->errors()['email'][0] ?? __('Unable to create invitation.'),
                    ]);
                }
            }

            if ($settingsService->isOnboardingComplete($workspace)) {
                $workspace->forceFill(['settings_onboarded_at' => now()])->save();

                Inertia::flash('toast', ['type' => 'success', 'message' => __('Business setup complete.')]);

                return to_route('dashboard');
            }

            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Please complete required setup fields before finishing.')]);

            return to_route('business-setup.onboarding', ['step' => 1]);
        }

        $nextStep = min($stepIndex + 1, 3);

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
