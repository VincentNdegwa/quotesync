<?php

namespace App\Services\Quotes;

use App\Models\QuoteTemplate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QuoteTemplateService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIndex(Workspace $workspace, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = QuoteTemplate::query()
            ->where('workspace_id', $workspace->id)
            ->withCount('sections');

        $search = trim((string) Arr::get($filters, 'search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        if (($active = Arr::get($filters, 'is_active')) !== null && $active !== '') {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOL));
        }

        return $query
            ->orderByDesc('is_system')
            ->orderByRaw('LOWER(name)')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): QuoteTemplate
    {
        return DB::transaction(function () use ($workspace, $payload): QuoteTemplate {
            $sections = Arr::pull($payload, 'sections', []);

            $template = QuoteTemplate::query()->create([
                ...$payload,
                'workspace_id' => $workspace->id,
            ]);

            $this->syncSections($template, $sections);

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(QuoteTemplate $template, array $payload): QuoteTemplate
    {
        return DB::transaction(function () use ($template, $payload): QuoteTemplate {
            $sections = Arr::pull($payload, 'sections', []);

            if ($template->is_system) {
                Arr::forget($payload, ['is_system']);
            }

            $template->fill($payload)->save();

            if (! $template->is_system) {
                $this->syncSections($template, $sections);
            }

            return $template->refresh();
        });
    }

    public function delete(QuoteTemplate $template): void
    {
        if ($template->is_system) {
            return;
        }

        $template->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toBuilderPayload(QuoteTemplate $template): array
    {
        $template->loadMissing('sections.lineItems.taxes');

        return [
            'id' => $template->id,
            'number' => null,
            'title' => $template->name,
            'status' => 'draft',
            'client_id' => null,
            'assigned_to' => null,
            'currency' => null,
            'valid_until' => null,
            'description' => $template->description,
            'industry' => $template->industry,
            'cover_message' => $template->cover_message,
            'notes' => $template->notes,
            'terms' => $template->terms,
            'template_id' => null,
            'requires_deposit' => false,
            'deposit_amount' => null,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'layout' => $template->layout,
            'is_active' => (bool) $template->is_active,
            'is_system' => (bool) $template->is_system,
            'sections' => $template->sections->map(fn ($section): array => [
                'id' => $section->id,
                'title' => $section->title,
                'sort_order' => $section->sort_order,
                'line_items' => $section->lineItems->map(fn ($lineItem): array => [
                    'id' => $lineItem->id,
                    'catalog_item_id' => $lineItem->catalog_item_id,
                    'name' => $lineItem->name,
                    'description' => $lineItem->description,
                    'quantity' => (float) $lineItem->quantity,
                    'unit' => $lineItem->unit,
                    'unit_price' => (float) $lineItem->unit_price,
                    'discount_percent' => (float) $lineItem->discount_percent,
                    'is_optional' => (bool) $lineItem->is_optional,
                    'notes' => $lineItem->notes,
                    'sort_order' => $lineItem->sort_order,
                    'taxes' => $lineItem->taxes->map(fn ($tax): array => [
                        'tax_id' => $tax->tax_id,
                        'tax_label' => $tax->tax_label,
                        'tax_rate' => (float) $tax->tax_rate,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function syncSections(QuoteTemplate $template, mixed $sections): void
    {
        $template->sections()->delete();

        if (! is_array($sections)) {
            return;
        }

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = $template->sections()->create([
                'title' => (string) Arr::get($sectionData, 'title', 'Section'),
                'sort_order' => (int) Arr::get($sectionData, 'sort_order', $sectionIndex),
            ]);

            $lineItems = Arr::get($sectionData, 'line_items', []);

            if (! is_array($lineItems)) {
                continue;
            }

            foreach ($lineItems as $lineItemIndex => $lineItemData) {
                $lineItem = $section->lineItems()->create([
                    'quote_template_id' => $template->id,
                    'catalog_item_id' => Arr::get($lineItemData, 'catalog_item_id'),
                    'name' => (string) Arr::get($lineItemData, 'name', 'Line item'),
                    'description' => Arr::get($lineItemData, 'description'),
                    'quantity' => (float) Arr::get($lineItemData, 'quantity', 1),
                    'unit' => Arr::get($lineItemData, 'unit'),
                    'unit_price' => (float) Arr::get($lineItemData, 'unit_price', 0),
                    'discount_percent' => (float) Arr::get($lineItemData, 'discount_percent', 0),
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
