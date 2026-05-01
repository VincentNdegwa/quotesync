<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogItemRequest;
use App\Http\Requests\UpdateCatalogItemRequest;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\ConfigurationUnit;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Catalog\CatalogItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogItemController extends Controller
{
    public function index(Request $request, CatalogItemService $catalogItemService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->string('category_id')->toString(),
            'is_active' => $request->string('is_active')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        return Inertia::render('catalog/Index', [
            'filters' => $filters,
            'items' => $catalogItemService->paginateForIndex($workspace, $filters)
                ->through(fn (CatalogItem $item): array => [
                    ...$item->toArray(),
                    'taxes' => $item->taxes,
                    'tax_ids' => $item->taxes->pluck('id')->values()->all(),
                    'configuration_unit' => $item->configurationUnit,
                ]),
            'categories' => CatalogCategory::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderByRaw('LOWER(name)')
                ->get(),
            'taxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(),
            'units' => ConfigurationUnit::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(),
        ]);
    }

    public function store(StoreCatalogItemRequest $request, CatalogItemService $catalogItemService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $payload = $request->validated();
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['created_by'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')?->store(
                "workspaces/{$workspace->id}/catalog",
                'public',
            );
        }

        $catalogItemService->create($workspace, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catalog item created.')]);

        return back();
    }

    public function show(Request $request, CatalogItem $catalog): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $catalog->load(['category:id,name', 'taxes:id,name,rate', 'configurationUnit:id,name,symbol']);

        return Inertia::render('catalog/Show', [
            'item' => [
                ...$catalog->toArray(),
                'taxes' => $catalog->taxes,
                'tax_ids' => $catalog->taxes->pluck('id')->values()->all(),
                'configuration_unit' => $catalog->configurationUnit,
            ],
            'availableTaxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(),
            'units' => ConfigurationUnit::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(),
            'margin' => [
                'profit_per_unit' => (float) $catalog->unit_price - (float) $catalog->cost_price,
                'margin_percent' => (float) $catalog->unit_price > 0
                    ? round((((float) $catalog->unit_price - (float) $catalog->cost_price) / (float) $catalog->unit_price) * 100, 2)
                    : 0,
            ],
        ]);
    }

    public function update(UpdateCatalogItemRequest $request, CatalogItem $catalog, CatalogItemService $catalogItemService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $payload = $request->validated();

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')?->store(
                "workspaces/{$workspace->id}/catalog",
                'public',
            );
        }

        $catalogItemService->update($catalog, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catalog item updated.')]);

        return back();
    }

    public function destroy(Request $request, CatalogItem $catalog, CatalogItemService $catalogItemService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $catalogItemService->delete($catalog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catalog item deleted.')]);

        return back();
    }

    public function bulkAction(Request $request, CatalogItemService $catalogItemService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:activate,deactivate,delete,change_category'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $count = $catalogItemService->bulkAction(
            $workspace,
            $validated['ids'],
            $validated['action'],
            $validated['category_id'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count record updated.|:count records updated.', $count, ['count' => $count]),
        ]);

        return back();
    }
}
