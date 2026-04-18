<?php

namespace App\Services\Catalog;

use App\Models\CatalogItem;
use App\Models\Tax;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CatalogItemService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIndex(Workspace $workspace, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->with(['category:id,name', 'taxes:id,name,rate']);

        $search = trim((string) Arr::get($filters, 'search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (($categoryId = Arr::get($filters, 'category_id')) !== null && $categoryId !== '') {
            $query->where('catalog_category_id', (int) $categoryId);
        }

        if (($active = Arr::get($filters, 'is_active')) !== null && $active !== '') {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOL));
        }

        match (Arr::get($filters, 'sort', 'newest')) {
            'name' => $query->orderByRaw('LOWER(name)'),
            'price' => $query->orderByDesc('unit_price'),
            'usage' => $query->orderByDesc('usage_count'),
            'margin' => $query->orderByRaw('(unit_price - cost_price) desc'),
            default => $query->latest('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): CatalogItem
    {
        $taxIds = Arr::pull($payload, 'tax_ids', []);

        $item = CatalogItem::query()->create([
            ...$payload,
            'workspace_id' => $workspace->id,
        ]);

        $item->taxes()->sync($this->resolveTaxIds($workspace, $taxIds));

        return $item->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(CatalogItem $item, array $payload): CatalogItem
    {
        $taxIds = Arr::pull($payload, 'tax_ids', []);

        $item->fill([
            ...$payload,
        ])->save();

        $item->taxes()->sync($this->resolveTaxIds($item->workspace, $taxIds));

        return $item->refresh();
    }

    public function delete(CatalogItem $item): void
    {
        $item->delete();
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkAction(Workspace $workspace, array $ids, string $action, ?int $categoryId = null): int
    {
        $query = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids);

        return match ($action) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'change_category' => $query->update(['catalog_category_id' => $categoryId]),
            'delete' => $query->delete(),
            default => 0,
        };
    }

    /**
     * @param  array<int, int|string>|mixed  $taxIds
     * @return array<int, int>
     */
    private function resolveTaxIds(Workspace $workspace, mixed $taxIds): array
    {
        if (! is_array($taxIds) || $taxIds === []) {
            return [];
        }

        return Tax::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $taxIds)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
    }
}
