<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LoadWorkspaceUsageCounts
{
    public function handle(Request $request, Closure $next)
    {
        if ($workspace = $request->attributes->get('workspace')) {
            $workspace->loadCount(['members', 'catalogItems', 'templates', 'clients', 'followUpSequences']);
            $workspace->load(['owner.workspaces', 'usage']);
        }

        return $next($request);
    }
}
