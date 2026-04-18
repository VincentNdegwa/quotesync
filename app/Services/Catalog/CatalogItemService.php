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
            ->with(['category:id,name', 'tax:id,name,rate']);

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
        $taxRate = $this->resolveTaxRate($workspace, $payload);

        return CatalogItem::query()->create([
            ...$payload,
            'workspace_id' => $workspace->id,
            'tax_rate' => $taxRate,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(CatalogItem $item, array $payload): CatalogItem
    {
        $workspace = $item->workspace;
        $taxRate = $this->resolveTaxRate($workspace, $payload);

        $item->fill([
            ...$payload,
            'tax_rate' => $taxRate,
        ])->save();

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
     * @param  array<string, mixed>  $payload
     */
    private function resolveTaxRate(Workspace $workspace, array $payload): float
    {
        if (Arr::has($payload, 'tax_id') && Arr::get($payload, 'tax_id') !== null) {
            $tax = Tax::query()
                ->where('workspace_id', $workspace->id)
                ->find((int) Arr::get($payload, 'tax_id'));

            if ($tax !== null) {
                return (float) $tax->rate;
            }
        }

        return (float) Arr::get($payload, 'tax_rate', 0);
    }
}
