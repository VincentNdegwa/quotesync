<?php

namespace App\Http\Controllers;

use App\Http\Requests\Configuration\ConfigIndustryFormRequest;
use App\Models\ConfigIndustry;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigIndustryController extends Controller
{
    public function store(ConfigIndustryFormRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        ConfigIndustry::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'is_active' => (bool) $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Industry created.')]);

        return back();
    }

    public function update(ConfigIndustryFormRequest $request, ConfigIndustry $industry): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $industry->workspace_id === $workspace->id, 404);

        $industry->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Industry updated.')]);

        return back();
    }

    public function destroy(Request $request, ConfigIndustry $industry): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $industry->workspace_id === $workspace->id, 404);

        $industry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Industry deleted.')]);

        return back();
    }
}
