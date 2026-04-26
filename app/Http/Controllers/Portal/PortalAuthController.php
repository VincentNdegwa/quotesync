<?php

namespace App\Http\Controllers\Portal;

use App\Models\PortalInvitation;
use App\Models\PortalMagicLink;
use App\Models\PortalUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class PortalAuthController
{
    public function showLoginForm(): Response
    {
        return Inertia::render('portal/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('portal')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function loginWithMagicLink(string $token): RedirectResponse
    {
        $magicLink = PortalMagicLink::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->with('client')
            ->firstOrFail();

        if (!$magicLink->isValid()) {
            return redirect()->route('portal.login')->withErrors([
                'email' => 'This magic link has expired or already been used.',
            ]);
        }

        $portalUser = PortalUser::where('email', $magicLink->email)
            ->where('workspace_id', $magicLink->workspace_id)
            ->first();

        if (!$portalUser) {
            return redirect()->route('portal.login')->withErrors([
                'email' => 'No account found for this email. Please register first.',
            ]);
        }

        $magicLink->markAsUsed();

        Auth::guard('portal')->login($portalUser);

        return redirect()->route('portal.dashboard');
    }

    public function showRegistrationForm(string $token): Response
    {
        $invitation = PortalInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('client', 'workspace')
            ->firstOrFail();

        return Inertia::render('portal/Auth/Register', [
            'invitation' => $invitation,
        ]);
    }

    public function register(Request $request, string $token): RedirectResponse
    {
        $invitation = PortalInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $portalUser = PortalUser::create([
            'workspace_id' => $invitation->workspace_id,
            'client_id' => $invitation->client_id,
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
        ]);

        $invitation->update(['accepted_at' => now()]);

        event(new Registered($portalUser));

        Auth::guard('portal')->login($portalUser);

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function switchWorkspace(Request $request): RedirectResponse
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $portalUser = Auth::guard('portal')->user();
        $workspaceId = $request->workspace_id;

        // Verify the portal user has an accepted invitation to this workspace
        $hasAccess = \App\Models\PortalInvitation::where('email', $portalUser->email)
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('accepted_at')
            ->exists();

        if (!$hasAccess) {
            return back()->withErrors([
                'workspace_id' => 'You do not have access to this workspace.',
            ]);
        }

        // Store the selected workspace in session
        session(['portal_current_workspace_id' => $workspaceId]);

        return redirect()->route('portal.dashboard');
    }
}
