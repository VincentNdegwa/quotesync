<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceSwitchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->belongsToWorkspace($workspace), 403);

        $user->switchWorkspace($workspace);

        return back();
    }
}
