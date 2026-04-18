<?php

namespace App\Services\Quotes;

use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIndex(Workspace $workspace, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->with(['client:id,company_name', 'assignee:id,name']);

        $search = trim((string) Arr::get($filters, 'search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('company_name', 'like', "%{$search}%"));
            });
        }

        $status = trim((string) Arr::get($filters, 'status', ''));

        if ($status !== '') {
            $query->where('status', $status);
        }

        match (Arr::get($filters, 'sort', 'newest')) {
            'number' => $query->orderBy('number'),
            'amount' => $query->orderByDesc('total'),
            'valid_until' => $query->orderBy('valid_until'),
            default => $query->latest('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): Quote
    {
        return DB::transaction(function () use ($workspace, $payload): Quote {
            $sections = Arr::pull($payload, 'sections', []);

            $quote = Quote::query()->create([
                ...$payload,
                'workspace_id' => $workspace->id,
                'status' => Arr::get($payload, 'status', 'draft'),
            ]);

            $this->syncSections($quote, $sections);

            return $quote->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Quote $quote, array $payload): Quote
    {
        return DB::transaction(function () use ($quote, $payload): Quote {
            $sections = Arr::pull($payload, 'sections', []);

            $quote->fill($payload)->save();

            $this->syncSections($quote, $sections);

            return $quote->refresh();
        });
    }

    public function delete(Quote $quote): void
    {
        $quote->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toBuilderPayload(Quote $quote): array
    {
        $quote->loadMissing([
            'sections.lineItems.taxes',
        ]);

        return [
            'id' => $quote->id,
            'number' => $quote->number,
            'title' => $quote->title,
            'status' => $quote->status,
            'client_id' => $quote->client_id,
            'assigned_to' => $quote->assigned_to,
            'currency' => $quote->currency,
            'valid_until' => $quote->valid_until?->toDateString(),
            'cover_message' => $quote->cover_message,
            'terms' => $quote->terms,
            'notes' => $quote->notes,
            'template_id' => $quote->template_id,
            'requires_deposit' => (bool) $quote->requires_deposit,
            'deposit_amount' => $quote->deposit_amount,
            'subtotal' => $quote->subtotal,
            'discount_amount' => $quote->discount_amount,
            'tax_amount' => $quote->tax_amount,
            'total' => $quote->total,
            'sections' => $quote->sections->map(function ($section): array {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'line_items' => $section->lineItems->map(function ($lineItem): array {
                        return [
                            'id' => $lineItem->id,
                            'catalog_item_id' => $lineItem->catalog_item_id,
                            'name' => $lineItem->name,
                            'description' => $lineItem->description,
                            'quantity' => (float) $lineItem->quantity,
                            'unit' => $lineItem->unit,
                            'unit_price' => (float) $lineItem->unit_price,
                            'discount_percent' => (float) $lineItem->discount_percent,
                            'subtotal' => (float) $lineItem->subtotal,
                            'tax_amount' => (float) $lineItem->tax_amount,
                            'total' => (float) $lineItem->total,
                            'is_optional' => (bool) $lineItem->is_optional,
                            'notes' => $lineItem->notes,
                            'sort_order' => $lineItem->sort_order,
                            'taxes' => $lineItem->taxes->map(fn ($tax): array => [
                                'tax_id' => $tax->tax_id,
                                'tax_label' => $tax->tax_label,
                                'tax_rate' => (float) $tax->tax_rate,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function initialPayloadFromTemplate(QuoteTemplate $template, Workspace $workspace): array
    {
        $template->loadMissing('sections.lineItems.taxes');

        $catalogItems = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $template->sections
                ->flatMap(fn ($section) => $section->lineItems->pluck('catalog_item_id'))
                ->filter()
                ->values()
                ->all())
            ->with('taxes:id,name,rate')
            ->get(['id', 'unit_price']);

        $catalogById = $catalogItems->keyBy('id');

        return [
            'title' => $template->name,
            'template_id' => $template->id,
            'cover_message' => $template->cover_message,
            'terms' => $template->terms,
            'notes' => $template->notes,
            'sections' => $template->sections->map(function ($section) use ($catalogById): array {
                return [
                    'id' => null,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'line_items' => $section->lineItems->map(function ($lineItem) use ($catalogById): array {
                        $catalog = $lineItem->catalog_item_id ? $catalogById->get($lineItem->catalog_item_id) : null;

                        $resolvedTaxes = $catalog?->taxes?->map(fn ($tax): array => [
                            'tax_id' => $tax->id,
                            'tax_label' => $tax->name,
                            'tax_rate' => (float) $tax->rate,
                        ])->values()->all() ?? $lineItem->taxes->map(fn ($tax): array => [
                            'tax_id' => $tax->tax_id,
                            'tax_label' => $tax->tax_label,
                            'tax_rate' => (float) $tax->tax_rate,
                        ])->values()->all();

                        return [
                            'id' => null,
                            'catalog_item_id' => $lineItem->catalog_item_id,
                            'name' => $lineItem->name,
                            'description' => $lineItem->description,
                            'quantity' => (float) $lineItem->quantity,
                            'unit' => $lineItem->unit,
                            'unit_price' => $catalog ? (float) $catalog->unit_price : (float) $lineItem->unit_price,
                            'discount_percent' => (float) $lineItem->discount_percent,
                            'subtotal' => 0,
                            'tax_amount' => 0,
                            'total' => 0,
                            'is_optional' => (bool) $lineItem->is_optional,
                            'notes' => $lineItem->notes,
                            'sort_order' => $lineItem->sort_order,
                            'taxes' => $resolvedTaxes,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function syncSections(Quote $quote, array $sections): void
    {
        $quote->sections()->delete();

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = $quote->sections()->create([
                'title' => (string) Arr::get($sectionData, 'title', 'Section'),
                'sort_order' => (int) Arr::get($sectionData, 'sort_order', $sectionIndex),
            ]);

            $lineItems = Arr::get($sectionData, 'line_items', []);

            if (! is_array($lineItems)) {
                continue;
            }

            foreach ($lineItems as $lineItemIndex => $lineItemData) {
                $lineItem = $section->lineItems()->create([
                    'quote_id' => $quote->id,
                    'catalog_item_id' => Arr::get($lineItemData, 'catalog_item_id'),
                    'name' => (string) Arr::get($lineItemData, 'name', 'Line item'),
                    'description' => Arr::get($lineItemData, 'description'),
                    'quantity' => (float) Arr::get($lineItemData, 'quantity', 1),
                    'unit' => Arr::get($lineItemData, 'unit'),
                    'unit_price' => (float) Arr::get($lineItemData, 'unit_price', 0),
                    'discount_percent' => (float) Arr::get($lineItemData, 'discount_percent', 0),
                    'subtotal' => (float) Arr::get($lineItemData, 'subtotal', 0),
                    'tax_amount' => (float) Arr::get($lineItemData, 'tax_amount', 0),
                    'total' => (float) Arr::get($lineItemData, 'total', 0),
                    'is_optional' => (bool) Arr::get($lineItemData, 'is_optional', false),
                    'notes' => Arr::get($lineItemData, 'notes'),
                    'sort_order' => (int) Arr::get($lineItemData, 'sort_order', $lineItemIndex),
                ]);

                $taxes = Arr::get($lineItemData, 'taxes', []);

                if (! is_array($taxes)) {
                    continue;
                }

                foreach ($taxes as $taxData) {
                    $lineItem->taxes()->create([
                        'tax_id' => Arr::get($taxData, 'tax_id'),
                        'tax_label' => (string) Arr::get($taxData, 'tax_label', 'Tax'),
                        'tax_rate' => (float) Arr::get($taxData, 'tax_rate', 0),
                    ]);
                }
            }
        }
    }
}
