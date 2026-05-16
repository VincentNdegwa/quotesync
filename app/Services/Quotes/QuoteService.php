<?php

namespace App\Services\Quotes;

use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\QuoteTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteService
{
    public function __construct(
        private WorkspaceSettingsService $workspaceSettingsService,
        private QuoteNumberService $quoteNumberService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIndex(Workspace $workspace, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Quote::query()
            ->where('workspace_id', $workspace->id)
            // ->parents()
            ->with(['client:id,company_name,email', 'assignee:id,name', 'winProbability.signals']);

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

    public function allForKanban(Workspace $workspace, int $limit = 500): array
    {
        return Quote::query()
            ->where('workspace_id', $workspace->id)
            ->with(['client:id,company_name,email'])
            ->latest('created_at')
            ->limit($limit)
            ->get([
                'id', 'quote_uuid', 'number', 'title', 'status',
                'total', 'base_total', 'currency', 'base_currency', 'valid_until', 'created_at', 'client_id',
            ])
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'quote_uuid' => $quote->quote_uuid,
                'number' => $quote->number,
                'title' => $quote->title,
                'status' => $quote->status->value,
                'total' => (float) ($quote->base_total ?? $quote->total),
                'base_total' => $quote->base_total ? (float) $quote->base_total : null,
                'currency' => $quote->base_currency ?? $quote->currency,
                'base_currency' => $quote->base_currency,
                'valid_until' => $quote->valid_until?->toDateString(),
                'created_at' => $quote->created_at?->toISOString(),
                'client' => $quote->client ? [
                    'id' => $quote->client->id,
                    'company_name' => $quote->client->company_name,
                    'email' => $quote->client->email,
                ] : null,
                'assignee' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): Quote
    {
        return DB::transaction(function () use ($workspace, $payload): Quote {
            $this->workspaceSettingsService->syncDefaults($workspace);

            $sections = Arr::pull($payload, 'sections', []);
            $layoutSnapshot = Arr::pull($payload, 'layout_snapshot');
            $layout = Arr::pull($payload, 'layout');
            $templateId = Arr::get($payload, 'template_id');
            $quoteSettings = $this->quoteSettings($workspace);

            if (! is_array($layoutSnapshot) && is_array($layout)) {
                $layoutSnapshot = $layout;
            }

            $number = trim((string) Arr::get($payload, 'number', ''));

            if ($number === '') {
                $payload['number'] = $this->quoteNumberService->generate($workspace);
            }

            if (Arr::get($payload, 'valid_until') === null) {
                $validityDays = max(1, (int) Arr::get($quoteSettings, 'quote_validity_days', 30));
                $payload['valid_until'] = now()->addDays($validityDays)->toDateString();
            }

            if (! is_array($layoutSnapshot) && $templateId) {
                $templateLayout = QuoteTemplate::query()
                    ->where('workspace_id', $workspace->id)
                    ->whereKey($templateId)
                    ->value('layout');

                $layoutSnapshot = is_array($templateLayout) ? $templateLayout : null;
            }

            $quote = Quote::query()->create([
                ...$payload,
                'workspace_id' => $workspace->id,
                'status' => Arr::get($payload, 'status', 'draft'),
                'layout_snapshot' => $layoutSnapshot,
            ]);

            $this->syncSections($quote, $sections);

            $this->calculateQuoteTotals($quote);

            return $quote->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteSettings(Workspace $workspace): array
    {
        $query = WorkspaceSetting::query()
            ->where('workspace_id', $workspace->id)
            ->where('group', 'quotes')
            ->whereIn('key', [
                'quote_validity_days',
            ]);

        $settings = $query->get(['key', 'value', 'cast'])->keyBy('key');
        $validityDays = $this->decodeWorkspaceSetting($settings->get('quote_validity_days')?->value, $settings->get('quote_validity_days')?->cast, 30);

        return [
            'quote_validity_days' => max(1, (int) $validityDays),
        ];
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    private function decodeWorkspaceSetting(?string $value, ?string $cast, $default)
    {
        if ($value === null) {
            return $default;
        }

        return match ($cast) {
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Quote $quote, array $payload): Quote
    {
        return DB::transaction(function () use ($quote, $payload): Quote {
            $sections = Arr::pull($payload, 'sections', []);
            $layoutSnapshot = Arr::pull($payload, 'layout_snapshot');
            $layout = Arr::pull($payload, 'layout');

            if (! is_array($layoutSnapshot) && is_array($layout)) {
                $layoutSnapshot = $layout;
            }

            if (isset($payload['status'])) {
                $newStatus = QuoteStatus::from($payload['status']);
                $currentStatus = $quote->status;

                if ($newStatus === $currentStatus) {
                    unset($payload['status']);
                } else {
                    if (! $currentStatus->canBeChangedManually() && $newStatus !== $currentStatus) {
                        throw new \InvalidArgumentException("Status cannot be changed manually from {$currentStatus->value}.");
                    }

                    $allowedTransitions = $currentStatus->allowedTransitions();
                    if (! in_array($newStatus, $allowedTransitions, true)) {
                        throw new \InvalidArgumentException("Invalid status transition from {$currentStatus->value} to {$newStatus->value}.");
                    }
                }
            }

            $quote->load('sections.lineItems.taxes');

            $quote->fill([
                ...$payload,
                'layout_snapshot' => is_array($layoutSnapshot) ? $layoutSnapshot : null,
            ])->save();

            $this->syncSections($quote, $sections);

            $quote->load('sections.lineItems.taxes');
            $this->calculateQuoteTotals($quote);

            return $quote->refresh();
        });
    }

    public function delete(Quote $quote): void
    {
        $quote->delete();
    }

    public function markAsWon(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote): Quote {
            $quote->status = QuoteStatus::Won;
            $quote->accepted_at = now();
            $quote->won_at = now();
            $quote->save();

            // Propagate acceptance to parent quote if this is a version
            if ($quote->parent_quote_id !== null) {
                $parent = $quote->parent;
                if ($parent) {
                    $parent->status = QuoteStatus::Won;
                    $parent->accepted_at = now();
                    $parent->won_at = now();
                    $parent->save();

                    QuoteActivity::query()->create([
                        'quote_id' => $parent->id,
                        'workspace_id' => $parent->workspace_id,
                        'user_id' => auth()->id(),
                        'type' => 'accepted',
                        'description' => "Quote accepted (version {$quote->version})",
                        'metadata' => ['version_id' => $quote->id, 'version' => $quote->version],
                    ]);
                }
            }

            QuoteActivity::query()->create([
                'quote_id' => $quote->id,
                'workspace_id' => $quote->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'accepted',
                'description' => 'Quote marked as won',
                'metadata' => null,
            ]);

            $quote->quoteFollowUps()
                ->where('status', QuoteFollowUpStatus::Pending->value)
                ->update([
                    'status' => QuoteFollowUpStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            if ($quote->client) {
                $quote->client->calculateHealthScore();
            }

            return $quote->refresh();
        });
    }

    public function markAsLost(Quote $quote, ?string $reason = null): Quote
    {
        return DB::transaction(function () use ($quote, $reason): Quote {
            $quote->status = QuoteStatus::Lost;
            $quote->declined_at = now();
            $quote->lost_at = now();
            $quote->decline_reason = $reason;
            $quote->save();

            QuoteActivity::query()->create([
                'quote_id' => $quote->id,
                'workspace_id' => $quote->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'declined',
                'description' => $reason ? "Quote marked as lost: {$reason}" : 'Quote marked as lost',
                'metadata' => ['reason' => $reason],
            ]);

            $quote->quoteFollowUps()
                ->where('status', QuoteFollowUpStatus::Pending->value)
                ->update([
                    'status' => QuoteFollowUpStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            if ($quote->client) {
                $quote->client->calculateHealthScore();
            }

            return $quote->refresh();
        });
    }

    private function replicateQuote(Quote $quote, string $titleSuffix, string $activityDescription, bool $isRevision = false): Quote
    {
        return DB::transaction(function () use ($quote, $titleSuffix, $activityDescription, $isRevision): Quote {
            $quote->load(['sections.lineItems.taxes', 'workspace']);

            $workspace = $quote->workspace;

            $newQuote = $quote->replicate();
            $newQuote->quote_uuid = (string) Str::uuid();
            $newQuote->number = $this->quoteNumberService->generate($workspace);
            $newQuote->title = "{$quote->title} {$titleSuffix}";
            $newQuote->status = QuoteStatus::Draft->value;
            $newQuote->valid_until = now()->addDays(30)->toDateString();
            $newQuote->created_by = auth()->id();

            if ($isRevision) {
                $newQuote->parent_quote_id = $quote->id;
                $newQuote->version = ($quote->versions()->max('version') ?? $quote->version) + 1;
            }

            $newQuote->save();

            // Update parent's active_version_id only if this is a revision of a parent quote
            if ($isRevision && $quote->parent_quote_id === null) {
                $quote->update(['active_version_id' => $newQuote->id]);
            }

            foreach ($quote->sections as $section) {
                $newSection = $section->replicate();
                $newSection->quote_id = $newQuote->id;
                $newSection->save();

                foreach ($section->lineItems as $lineItem) {
                    $newLineItem = $lineItem->replicate();
                    $newLineItem->quote_id = $newQuote->id;
                    $newLineItem->quote_section_id = $newSection->id;
                    $newLineItem->save();

                    foreach ($lineItem->taxes as $tax) {
                        $newTax = $tax->replicate();
                        $newTax->quote_line_item_id = $newLineItem->id;
                        $newTax->save();
                    }
                }
            }

            QuoteActivity::query()->create([
                'quote_id' => $newQuote->id,
                'workspace_id' => $newQuote->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'created',
                'description' => $activityDescription,
                'metadata' => $isRevision ? ['parent_quote_id' => $quote->id, 'version' => $newQuote->version] : null,
            ]);

            return $newQuote->refresh();
        });
    }

    public function duplicate(Quote $quote): Quote
    {
        return $this->replicateQuote($quote, '(Copy)', "Quote duplicated from #{$quote->number}", false);
    }

    public function revise(Quote $quote): Quote
    {
        abort_unless($quote->status->canBeRevised(), 403, 'This quote cannot be revised.');

        return $this->replicateQuote($quote, '(Revision)', "Quote revised from #{$quote->number}", true);
    }

    public function restoreVersion(Quote $parentQuote, Quote $version): Quote
    {
        abort_unless($version->parent_quote_id === $parentQuote->id, 403, 'Invalid version.');

        return DB::transaction(function () use ($parentQuote, $version): Quote {
            $parentQuote->update(['active_version_id' => $version->id]);

            QuoteActivity::query()->create([
                'quote_id' => $parentQuote->id,
                'workspace_id' => $parentQuote->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'restored',
                'description' => "Quote restored to version {$version->version}",
                'metadata' => ['version_id' => $version->id, 'version' => $version->version],
            ]);

            return $version->refresh();
        });
    }

    public function reopen(Quote $quote, string $validUntil): Quote
    {
        abort_unless($quote->status->canBeReopened(), 403, 'This quote cannot be reopened.');

        return DB::transaction(function () use ($quote, $validUntil): Quote {
            $quote->update([
                'status' => QuoteStatus::Draft->value,
                'valid_until' => $validUntil,
            ]);

            QuoteActivity::query()->create([
                'quote_id' => $quote->id,
                'workspace_id' => $quote->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'created',
                'description' => 'Quote reopened',
            ]);

            return $quote->refresh();
        });
    }

    public function archive(Quote $quote): void
    {
        abort_unless($quote->status->canBeArchived(), 403, 'This quote cannot be archived.');

        $quote->delete();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => auth()->id(),
            'type' => 'created',
            'description' => 'Quote archived',
        ]);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{processed:int,skipped:int,missing:int,skipped_details:array<int, array{id:int,status:string,reason:string}>}
     */
    public function bulkAction(Workspace $workspace, array $ids, string $action): array
    {
        $quotes = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->parents()
            ->whereIn('id', $ids)
            ->get(['id', 'workspace_id', 'status']);

        $eligibleIds = [];
        $skipped = [];

        foreach ($quotes as $quote) {
            $status = $quote->status instanceof QuoteStatus ? $quote->status : QuoteStatus::from($quote->status);

            $canProceed = match ($action) {
                'delete' => $status->canBeDeleted(),
                'archive' => $status->canBeArchived(),
                default => false,
            };

            if ($canProceed) {
                $eligibleIds[] = $quote->id;
            } else {
                $skipped[] = [
                    'id' => $quote->id,
                    'status' => $status->value,
                    'reason' => "Action '{$action}' not permitted for status {$status->value}.",
                ];
            }
        }

        if ($eligibleIds !== []) {
            Quote::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('id', $eligibleIds)
                ->delete();

            if ($action === 'archive') {
                $now = now();
                $userId = auth()->id();

                $activities = array_map(
                    fn (int $quoteId): array => [
                        'quote_id' => $quoteId,
                        'workspace_id' => $workspace->id,
                        'user_id' => $userId,
                        'type' => 'created',
                        'description' => 'Quote archived',
                        'metadata' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $eligibleIds,
                );

                QuoteActivity::query()->insert($activities);
            }
        }

        $missingCount = max(0, count($ids) - $quotes->count());

        return [
            'processed' => count($eligibleIds),
            'skipped' => count($skipped),
            'missing' => $missingCount,
            'skipped_details' => $skipped,
        ];
    }

    public function toBuilderPayload(Quote $quote): array
    {
        $quote->loadMissing([
            'sections.lineItems.taxes',
        ]);

        $payload = [
            'id' => $quote->id,
            'quote_uuid' => $quote->quote_uuid,
            'number' => $quote->number,
            'title' => $quote->title,
            'status' => $quote->status,
            'client_id' => $quote->client_id,
            'assigned_to' => $quote->assigned_to,
            'currency' => $quote->currency,
            'base_currency' => $quote->base_currency ?? $quote->currency,
            'fx_rate' => $quote->fx_rate,
            'base_total' => $quote->base_total,
            'valid_until' => $quote->valid_until?->toDateString(),
            'cover_message' => $quote->cover_message,
            'terms' => $quote->terms,
            'notes' => $quote->notes,
            'template_id' => $quote->template_id,
            'quote_uuid' => $quote->quote_uuid,
            'layout_snapshot' => $quote->layout_snapshot,
            'requires_deposit' => (bool) $quote->requires_deposit,
            'deposit_amount' => $quote->deposit_amount,
            'deposit_percent' => $quote->deposit_percent,
            'is_locked' => (bool) $quote->is_locked,
            'subtotal' => $quote->base_subtotal,
            'discount_amount' => $quote->base_discount_amount,
            'tax_amount' => $quote->base_tax_amount,
            'total' => $quote->base_total,
            'layout' => $quote->layout_snapshot,
            'sections' => $quote->sections->map(function ($section): array {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'line_items' => $section->lineItems->map(function ($lineItem): array {
                        return [
                            'id' => $lineItem->id,
                            'catalog_item_id' => $lineItem->catalog_item_id,
                            'catalog_item_variant_id' => $lineItem->catalog_item_variant_id,
                            'name' => $lineItem->name,
                            'description' => $lineItem->description,
                            'quantity' => (float) $lineItem->quantity,
                            'unit' => $lineItem->unit,
                            'unit_price' => (float) $lineItem->base_unit_price,
                            'cost_price' => $lineItem->cost_price,
                            'discount_percent' => (float) $lineItem->discount_percent,
                            'subtotal' => (float) $lineItem->base_subtotal,
                            'tax_amount' => (float) $lineItem->base_tax_amount,
                            'total' => (float) $lineItem->base_total,
                            'is_optional' => (bool) $lineItem->is_optional,
                            'notes' => $lineItem->notes,
                            'sort_order' => $lineItem->sort_order,
                            'taxes' => $lineItem->taxes->map(fn ($tax): array => [
                                'tax_id' => $tax->tax_id,
                                'tax_label' => $tax->tax_label,
                                'tax_rate' => (float) $tax->tax_rate,
                                'inclusive' => (bool) $tax->inclusive,
                                'tax_amount' => $tax->base_tax_amount,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];

        return $payload;
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
            'layout_snapshot' => $template->layout,
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
                            'tax_inclusive' => (bool) $tax->inclusive,
                        ])->values()->all() ?? $lineItem->taxes->map(fn ($tax): array => [
                            'tax_id' => $tax->tax_id,
                            'tax_label' => $tax->tax_label,
                            'tax_rate' => (float) $tax->tax_rate,
                            'tax_inclusive' => $tax->tax_inclusive ?? false,
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
                $taxes = Arr::get($lineItemData, 'taxes', []);

                // Ensure we use 'inclusive' consistently from payload
                $taxesArray = is_array($taxes) ? collect($taxes)->map(fn ($tax) => [
                    'tax_rate' => (float) Arr::get($tax, 'tax_rate', 0),
                    'inclusive' => (bool) (Arr::get($tax, 'inclusive') ?? Arr::get($tax, 'tax_inclusive', false)),
                ])->values()->all() : [];

                $calculatedTotals = TaxCalculator::calculateLineItemTotals(
                    (float) Arr::get($lineItemData, 'quantity', 1),
                    (float) Arr::get($lineItemData, 'unit_price', 0),
                    (float) Arr::get($lineItemData, 'discount_percent', 0),
                    $taxesArray,
                );

                $lineItem = $section->lineItems()->create([
                    'quote_id' => $quote->id,
                    'catalog_item_id' => Arr::get($lineItemData, 'catalog_item_id'),
                    'catalog_item_variant_id' => Arr::get($lineItemData, 'catalog_item_variant_id'),
                    'name' => (string) Arr::get($lineItemData, 'name', 'Line item'),
                    'description' => Arr::get($lineItemData, 'description'),
                    'quantity' => (float) Arr::get($lineItemData, 'quantity', 1),
                    'unit' => Arr::get($lineItemData, 'unit'),
                    'unit_price' => (float) Arr::get($lineItemData, 'unit_price', 0),
                    'cost_price' => Arr::get($lineItemData, 'cost_price'),
                    'discount_percent' => (float) Arr::get($lineItemData, 'discount_percent', 0),
                    'subtotal' => $calculatedTotals['subtotal'],
                    'total' => $calculatedTotals['total'],
                    'is_optional' => (bool) Arr::get($lineItemData, 'is_optional', false),
                    'notes' => Arr::get($lineItemData, 'notes'),
                    'sort_order' => (int) Arr::get($lineItemData, 'sort_order', $lineItemIndex),
                ]);

                $baseUnitPrice = (float) Arr::get($lineItemData, 'unit_price', 0);
                $baseSubtotal = $calculatedTotals['subtotal'];
                $baseTaxAmount = $calculatedTotals['taxAmount'];
                $baseTotal = $calculatedTotals['total'];

                $fxRate = $quote->fx_rate ?? 1.0;

                $lineItem->update([
                    'base_unit_price' => $baseUnitPrice,
                    'base_subtotal' => $baseSubtotal,
                    'base_tax_amount' => $baseTaxAmount,
                    'base_total' => $baseTotal,
                    'unit_price' => $baseUnitPrice * $fxRate,
                    'subtotal' => $baseSubtotal * $fxRate,
                    'total' => $baseTotal * $fxRate,
                ]);

                if (! is_array($taxes)) {
                    continue;
                }

                foreach ($taxes as $index => $taxData) {
                    $inclusiveValue = Arr::get($taxData, 'inclusive') ?? Arr::get($taxData, 'tax_inclusive', false);
                    $taxBreakdown = $calculatedTotals['taxBreakdown'][$index] ?? null;
                    $baseTaxAmount = $taxBreakdown['tax_amount'] ?? 0;

                    $lineItem->taxes()->create([
                        'tax_id' => Arr::get($taxData, 'tax_id'),
                        'tax_label' => (string) Arr::get($taxData, 'tax_label', 'Tax'),
                        'tax_rate' => (float) Arr::get($taxData, 'tax_rate', 0),
                        'inclusive' => (bool) $inclusiveValue,
                        'tax_amount' => $baseTaxAmount * ($quote->fx_rate ?? 1.0),
                        'base_tax_amount' => $baseTaxAmount,
                    ]);
                }
            }
        }
    }

    private function calculateQuoteTotals(Quote $quote): void
    {
        $baseSubtotal = 0;
        $baseDiscountAmount = 0;
        $baseTaxAmount = 0;

        foreach ($quote->sections as $section) {
            foreach ($section->lineItems as $lineItem) {
                if ($lineItem->is_optional) {
                    continue;
                }

                $baseSubtotal += $lineItem->base_subtotal;
                $baseDiscountAmount += ($lineItem->quantity * $lineItem->base_unit_price * $lineItem->discount_percent / 100);
                $baseTaxAmount += $lineItem->base_tax_amount;
            }
        }

        $baseTotal = $baseSubtotal + $baseTaxAmount - $baseDiscountAmount;

        $quoteSubtotal = $baseSubtotal;
        $quoteDiscountAmount = $baseDiscountAmount;
        $quoteTaxAmount = $baseTaxAmount;
        $quoteTotal = $baseTotal;

        if ($quote->fx_rate && $quote->base_currency && $quote->base_currency !== $quote->currency) {
            $quoteSubtotal = $baseSubtotal * $quote->fx_rate;
            $quoteDiscountAmount = $baseDiscountAmount * $quote->fx_rate;
            $quoteTaxAmount = $baseTaxAmount * $quote->fx_rate;
            $quoteTotal = $baseTotal * $quote->fx_rate;
        }

        $quote->update([
            'subtotal' => $quoteSubtotal,
            'discount_amount' => $quoteDiscountAmount,
            'tax_amount' => $quoteTaxAmount,
            'total' => $quoteTotal,
            'base_total' => $baseTotal,
            'base_subtotal' => $baseSubtotal,
            'base_discount_amount' => $baseDiscountAmount,
            'base_tax_amount' => $baseTaxAmount,
        ]);
    }

    public function handover(Quote $quote, int $newAssigneeId): Quote
    {
        abort_if($quote->workspace_id !== auth()->user()?->current_workspace_id, 403);

        $newAssignee = User::query()
            ->where('id', $newAssigneeId)
            ->whereHas('workspaces', fn ($q) => $q->where('workspace_id', $quote->workspace_id))
            ->firstOrFail();

        $oldAssigneeId = $quote->assigned_to;

        $quote->update([
            'assigned_to' => $newAssigneeId,
        ]);

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => auth()->id(),
            'type' => 'updated',
            'description' => 'Quote ownership transferred',
            'metadata' => [
                'from_user_id' => $oldAssigneeId,
                'to_user_id' => $newAssigneeId,
            ],
        ]);

        return $quote->refresh();
    }
}
