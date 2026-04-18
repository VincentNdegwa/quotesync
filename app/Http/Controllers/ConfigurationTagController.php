<?php

namespace App\Http\Controllers;

use App\Http\Requests\Configuration\StoreConfigurationTagRequest;
use App\Http\Requests\Configuration\UpdateConfigurationTagRequest;
use App\Models\ConfigurationTag;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigurationTagController extends Controller
{
    public function store(StoreConfigurationTagRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        ConfigurationTag::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'is_active' => (bool) $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag created.')]);

        return back();
    }

    public function update(UpdateConfigurationTagRequest $request, ConfigurationTag $tag): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $tag->workspace_id === $workspace->id, 404);

        $tag->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag updated.')]);

        return back();
    }

    public function destroy(Request $request, ConfigurationTag $tag): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $tag->workspace_id === $workspace->id, 404);

        $tag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag deleted.')]);

        return back();
    }
}
