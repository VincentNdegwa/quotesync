<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\ConfigurationTag;
use App\Models\ConfigurationUnit;
use App\Models\Tax;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(): RedirectResponse
    {
        return to_route('configuration.taxes');
    }

    public function taxes(Request $request): Response
    {
        $workspace = $this->workspaceFromRequest($request);

        return Inertia::render('configuration/taxes/Index', [
            'taxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'rate', 'is_default', 'is_active', 'created_at']),
        ]);
    }

    public function categories(Request $request): Response
    {
        $workspace = $this->workspaceFromRequest($request);

        return Inertia::render('configuration/categories/Index', [
            'categories' => CatalogCategory::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'sort_order', 'is_active', 'created_at']),
        ]);
    }

    public function tags(Request $request): Response
    {
        $workspace = $this->workspaceFromRequest($request);

        return Inertia::render('configuration/tags/Index', [
            'tags' => ConfigurationTag::query()
                ->where('workspace_id', $workspace->id)
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'is_active', 'created_at']),
        ]);
    }

    public function units(Request $request): Response
    {
        $workspace = $this->workspaceFromRequest($request);

        return Inertia::render('configuration/units/Index', [
            'units' => ConfigurationUnit::query()
                ->where('workspace_id', $workspace->id)
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'symbol', 'is_active', 'created_at']),
        ]);
    }

    private function workspaceFromRequest(Request $request): Workspace
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        return $workspace;
    }
}
