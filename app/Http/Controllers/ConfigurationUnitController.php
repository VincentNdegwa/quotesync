<?php

namespace App\Http\Controllers;

use App\Http\Requests\Configuration\StoreConfigurationUnitRequest;
use App\Http\Requests\Configuration\UpdateConfigurationUnitRequest;
use App\Models\ConfigurationUnit;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigurationUnitController extends Controller
{
    public function store(StoreConfigurationUnitRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        ConfigurationUnit::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'is_active' => (bool) $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return back();
    }

    public function update(UpdateConfigurationUnitRequest $request, ConfigurationUnit $unit): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $unit->workspace_id === $workspace->id, 404);

        $unit->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return back();
    }

    public function destroy(Request $request, ConfigurationUnit $unit): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $unit->workspace_id === $workspace->id, 404);

        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return back();
    }
}
