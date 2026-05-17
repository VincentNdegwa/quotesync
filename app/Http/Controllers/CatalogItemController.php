<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\Catalog\CatalogItemBulkActionRequest;
use App\Http\Requests\Catalog\CatalogItemPriceTierRequest;
use App\Http\Requests\Catalog\CatalogItemVariantRequest;
use App\Http\Requests\Catalog\StoreCatalogItemRequest;
use App\Http\Requests\Catalog\UpdateCatalogItemRequest;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\CatalogItemPriceTier;
use App\Models\CatalogItemVariant;
use App\Models\ConfigurationUnit;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Catalog\CatalogItemService;
use App\Services\FileStorageService;
use App\Services\UsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogItemController extends Controller
{
    public function __construct(
        private UsageLimitService $usageLimitService,
    ) {}

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
            'items' => $catalogItemService->paginateForIndex($workspace, $filters),
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

    public function store(StoreCatalogItemRequest $request, CatalogItemService $catalogItemService, FileStorageService $fileStorageService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_CATALOG_ITEMS)) {
            throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_CATALOG_ITEMS));
        }

        $payload = $request->validated();
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['created_by'] = $request->user()?->id;

        if ($request->hasFile('image')) {
            $result = $fileStorageService->store($request->file('image'), "workspaces/{$workspace->id}/catalog");
            if (! $result['error']) {
                $payload['image_url'] = $result['url'];
            }
        }

        $catalogItemService->create($workspace, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Catalog item created.')]);

        return back();
    }

    public function show(Request $request, CatalogItem $catalog): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $catalog->load(['category:id,name', 'taxes:id,name,rate', 'configurationUnit:id,name,symbol', 'variants', 'priceTiers']);

        return Inertia::render('catalog/Show', [
            'item' => $catalog,
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

    public function update(UpdateCatalogItemRequest $request, CatalogItem $catalog, CatalogItemService $catalogItemService, FileStorageService $fileStorageService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $payload = $request->validated();

        if ($request->hasFile('image')) {
            $result = $fileStorageService->store($request->file('image'), "workspaces/{$workspace->id}/catalog");
            if (! $result['error']) {
                $payload['image_url'] = $result['url'];
            }
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

    public function bulkAction(CatalogItemBulkActionRequest $request, CatalogItemService $catalogItemService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

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

    public function storeVariant(CatalogItemVariantRequest $request, CatalogItem $catalog): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $validated = $request->validated();

        CatalogItemVariant::create([
            ...$validated,
            'catalog_item_id' => $catalog->id,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant created.')]);

        return back();
    }

    public function updateVariant(CatalogItemVariantRequest $request, CatalogItem $catalog, CatalogItemVariant $variant): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless(
            $workspace instanceof Workspace
            && $catalog->workspace_id === $workspace->id
            && $variant->catalog_item_id === $catalog->id,
            404
        );

        $validated = $request->validated();

        $variant->update([
            ...$validated,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant updated.')]);

        return back();
    }

    public function destroyVariant(Request $request, CatalogItem $catalog, CatalogItemVariant $variant): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless(
            $workspace instanceof Workspace
            && $catalog->workspace_id === $workspace->id
            && $variant->catalog_item_id === $catalog->id,
            404
        );

        $variant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variant deleted.')]);

        return back();
    }

    public function storePriceTier(CatalogItemPriceTierRequest $request, CatalogItem $catalog): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $catalog->workspace_id === $workspace->id, 404);

        $validated = $request->validated();

        CatalogItemPriceTier::create([
            ...$validated,
            'catalog_item_id' => $catalog->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price tier created.')]);

        return back();
    }

    public function updatePriceTier(CatalogItemPriceTierRequest $request, CatalogItem $catalog, CatalogItemPriceTier $priceTier): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless(
            $workspace instanceof Workspace
            && $catalog->workspace_id === $workspace->id
            && $priceTier->catalog_item_id === $catalog->id,
            404
        );

        $validated = $request->validated();

        $priceTier->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price tier updated.')]);

        return back();
    }

    public function destroyPriceTier(Request $request, CatalogItem $catalog, CatalogItemPriceTier $priceTier): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless(
            $workspace instanceof Workspace
            && $catalog->workspace_id === $workspace->id
            && $priceTier->catalog_item_id === $catalog->id,
            404
        );

        $priceTier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Price tier deleted.')]);

        return back();
    }
}
