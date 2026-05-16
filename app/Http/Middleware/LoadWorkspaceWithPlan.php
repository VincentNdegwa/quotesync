<?php

namespace App\Http\Middleware;

use App\Services\WorkspacePlanCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoadWorkspaceWithPlan
{
    public function __construct(
        private WorkspacePlanCache $planCache,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $next($request);
        }

        // Append workspace to request attributes for use in controllers and form requests
        $request->attributes->set('workspace', $workspace);
        $request->attributes->set('workspace_plan', $this->planCache->getPlan($workspace));
        $request->attributes->set('workspace_plan_features', $this->planCache->getPlanFeatures($workspace));

        return $next($request);
    }
}
