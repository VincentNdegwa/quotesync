<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invitations\CreateInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Invitations\InvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(CreateInvitationRequest $request, InvitationService $invitationService): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $validated = $request->validated();

        $invitationService->create(
            $workspace,
            $request->user(),
            $validated['email'],
            (int) $validated['role_id'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }

    public function destroy(Request $request, string $code, InvitationService $invitationService): RedirectResponse
    {
        $invitation = Invitation::query()
            ->where('code', $code)
            ->firstOrFail();

        // Check if invitation belongs to user's workspace
        if ($invitation->workspace_id !== $request->user()->currentWorkspace->id) {
            abort(404);
        }

        try {
            $invitationService->cancel($invitation, $request->user());
        } catch (AuthorizationException $e) {
            abort(403);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return back();
    }

    public function accept(Request $request, Invitation $invitation, InvitationService $invitationService): RedirectResponse
    {
        if (! $invitation->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This invitation is no longer valid.')]);

            return to_route('login');
        }

        $user = $request->user();

        if ($user instanceof User) {
            $invitationService->accept($invitation, $user);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

            return to_route('dashboard');
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])
            ->exists();

        if ($existingUser) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Log in to accept your invitation.')]);

            return to_route('login', [
                'email' => $invitation->email,
                'invitation' => $invitation->code,
            ]);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Create your account to accept your invitation.')]);

        return to_route('register', [
            'email' => $invitation->email,
            'invitation' => $invitation->code,
        ]);
    }
}
