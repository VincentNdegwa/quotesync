<?php

namespace App\Http\Middleware;

use App\Models\PortalInvitation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetPortalWorkspaceContext
{
    public function handle(Request $request, Closure $next)
    {
        $portalUser = Auth::guard('portal')->user();

        if (! $portalUser) {
            return $next($request);
        }

        $sessionWorkspaceId = $request->session()->get('portal_current_workspace_id');
        $workspaceId = $sessionWorkspaceId ?? $portalUser->workspace_id;

        $invitation = PortalInvitation::where('email', $portalUser->email)
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('accepted_at')
            ->first();

        if ($invitation) {
            $request->attributes->add([
                'portal_workspace_id' => $workspaceId,
                'portal_client_id' => $invitation->client_id,
            ]);
        } else {
            $request->attributes->add([
                'portal_workspace_id' => $portalUser->workspace_id,
                'portal_client_id' => $portalUser->client_id,
            ]);
        }

        return $next($request);
    }
}
