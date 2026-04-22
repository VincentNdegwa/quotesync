<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogCategoryRequest;
use App\Http\Requests\UpdateCatalogCategoryRequest;
use App\Models\CatalogCategory;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        return response()->json([
            'data' => CatalogCategory::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderByRaw('LOWER(name)')
                ->get(),
        ]);
    }

    public function store(StoreCatalogCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $category = CatalogCategory::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'is_active' => (bool) $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

            return back();
        }

        return response()->json(['data' => $category], 201);
    }

    public function update(UpdateCatalogCategoryRequest $request, CatalogCategory $category): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $category->workspace_id === $workspace->id, 404);

        $category->fill($request->validated())->save();

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

            return back();
        }

        return response()->json(['data' => $category->refresh()]);
    }

    public function destroy(Request $request, CatalogCategory $category): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $category->workspace_id === $workspace->id, 404);

        $category->delete();

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

            return back();
        }

        return response()->json(status: 204);
    }
}
