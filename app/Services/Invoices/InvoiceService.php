<?php

namespace App\Services\Invoices;

use App\Enums\InvoiceActivityType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\InvoiceLineItemTax;
use App\Models\Quote;
use App\Models\Workspace;
use App\Services\Builder\BuilderLayoutService;
use App\Services\ExchangeRateService;
use App\Services\Quotes\TaxCalculator as QuotesTaxCalculator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        private InvoiceNumberingService $invoiceNumberingService,
        private ExchangeRateService $exchangeRateService,
        private BuilderLayoutService $builderLayoutService,
    ) {}

    public function convertFromQuote(Quote $quote, int $createdBy): Invoice
    {
        return DB::transaction(function () use ($quote, $createdBy): Invoice {
            $quote->load(['sections.lineItems.taxes', 'workspace']);

            $workspace = $quote->workspace;

            $invoice = Invoice::query()->create([
                'workspace_id' => $quote->workspace_id,
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'invoice_number' => $this->invoiceNumberingService->generateNextNumber($workspace),
                'title' => $quote->title,
                'cover_message' => $quote->cover_message,
                'terms' => $quote->terms,
                'notes' => $quote->notes,
                'layout_snapshot' => $quote->layout_snapshot,
                'currency' => $quote->currency,
                'base_currency' => $quote->base_currency,
                'fx_rate' => $quote->fx_rate,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'discount_amount' => $quote->discount_amount,
                'total' => $quote->total,
                'base_subtotal' => $quote->base_subtotal,
                'base_tax_amount' => $quote->base_tax_amount,
                'base_discount_amount' => $quote->base_discount_amount,
                'base_total' => $quote->base_total,
                'paid_amount' => 0,
                'status' => 'draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'created_by' => $createdBy,
            ]);

            foreach ($quote->sections as $section) {
                $invoiceSection = $invoice->sections()->create([
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                ]);

                foreach ($section->lineItems as $lineItem) {
                    $invoiceLineItem = $invoiceSection->lineItems()->create([
                        'invoice_id' => $invoice->id,
                        'catalog_item_id' => $lineItem->catalog_item_id,
                        'catalog_item_variant_id' => $lineItem->catalog_item_variant_id,
                        'name' => $lineItem->name,
                        'description' => $lineItem->description,
                        'quantity' => $lineItem->quantity,
                        'unit' => $lineItem->unit,
                        'unit_price' => $lineItem->unit_price,
                        'base_unit_price' => $lineItem->base_unit_price,
                        'tax_rate' => $lineItem->tax_rate,
                        'discount_type' => $lineItem->discount_type?->value,
                        'discount_value' => $lineItem->discount_value,
                        'subtotal' => $lineItem->subtotal,
                        'base_subtotal' => $lineItem->base_subtotal,
                        'tax_amount' => $lineItem->tax_amount,
                        'base_tax_amount' => $lineItem->base_tax_amount,
                        'total' => $lineItem->total,
                        'base_total' => $lineItem->base_total,
                        'sort_order' => $lineItem->sort_order,
                    ]);

                    // Create new invoice line item taxes
                    foreach ($lineItem->taxes as $tax) {
                        $invoiceLineItemTax = new InvoiceLineItemTax;
                        $invoiceLineItemTax->invoice_line_item_id = $invoiceLineItem->id;
                        $invoiceLineItemTax->tax_id = $tax->tax_id;
                        $invoiceLineItemTax->tax_label = $tax->tax_label;
                        $invoiceLineItemTax->tax_rate = $tax->tax_rate;
                        $invoiceLineItemTax->inclusive = $tax->inclusive;
                        $invoiceLineItemTax->tax_amount = $tax->tax_amount;
                        $invoiceLineItemTax->base_tax_amount = $tax->base_tax_amount;
                        $invoiceLineItemTax->save();
                    }
                }
            }

            return $invoice->refresh();
        });
    }

    private function replicateInvoice(Invoice $invoice, string $suffix, string $activityDescription): Invoice
    {
        return DB::transaction(function () use ($invoice, $suffix, $activityDescription): Invoice {
            $invoice->load(['sections.lineItems.taxes', 'workspace']);

            $workspace = $invoice->workspace;

            $newInvoice = $invoice->replicate();
            $newInvoice->invoice_number = $this->invoiceNumberingService->generateNextNumber($workspace);
            $newInvoice->title = $invoice->title.' '.$suffix;
            $newInvoice->status = 'draft';
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now()->addDays(30);
            $newInvoice->paid_amount = 0;
            $newInvoice->sent_at = null;
            $newInvoice->paid_date = null;
            $newInvoice->invoice_uuid = (string) Str::uuid();
            $newInvoice->save();

            foreach ($invoice->sections as $section) {
                $newSection = $section->replicate();
                $newSection->invoice_id = $newInvoice->id;
                $newSection->save();

                foreach ($section->lineItems as $lineItem) {
                    $newLineItem = $lineItem->replicate();
                    $newLineItem->invoice_id = $newInvoice->id;
                    $newLineItem->invoice_section_id = $newSection->id;
                    $newLineItem->save();

                    foreach ($lineItem->taxes as $tax) {
                        $newTax = $tax->replicate();
                        $newTax->invoice_line_item_id = $newLineItem->id;
                        $newTax->save();
                    }
                }
            }

            InvoiceActivity::query()->create([
                'invoice_id' => $newInvoice->id,
                'workspace_id' => $newInvoice->workspace_id,
                'user_id' => auth()->id(),
                'type' => 'created',
                'description' => $activityDescription,
                'metadata' => ['parent_invoice_id' => $invoice->id],
            ]);

            return $newInvoice->refresh();
        });
    }

    public function duplicate(Invoice $invoice): Invoice
    {
        return $this->replicateInvoice($invoice, '(Copy)', "Invoice duplicated from #{$invoice->invoice_number}");
    }

    public function toBuilderPayload(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'sections.lineItems.taxes',
            'sections.lineItems.priceTiers',
            'client',
        ]);

        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'name' => $invoice->title,
            'title' => $invoice->title,
            'status' => $invoice->status,
            'client_id' => $invoice->client_id,
            'client' => $invoice->client ? [
                'id' => $invoice->client->id,
                'company_name' => $invoice->client->company_name,
                'contact_name' => $invoice->client->contact_name,
                'email' => $invoice->client->email,
                'phone' => $invoice->client->phone,
                'address' => $invoice->client->address,
            ] : null,
            'currency' => $invoice->currency,
            'base_currency' => $invoice->base_currency ?? $invoice->currency,
            'fx_rate' => $invoice->fx_rate,
            'base_total' => $invoice->base_total,
            'issue_date' => $invoice->issue_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'valid_until' => $invoice->due_date?->toDateString(),
            'cover_message' => $invoice->cover_message ?? '',
            'terms' => $invoice->terms ?? '',
            'notes' => $invoice->notes ?? '',
            'quote_id' => $invoice->quote_id,
            'layout_snapshot' => $this->builderLayoutService->normalizeLayoutForRead($invoice->layout_snapshot),
            'subtotal' => $invoice->base_subtotal,
            'discount_amount' => $invoice->base_discount_amount,
            'tax_amount' => $invoice->base_tax_amount,
            'total' => $invoice->base_total,
            'layout' => $this->builderLayoutService->normalizeLayoutForRead($invoice->layout_snapshot),
            'sections' => $invoice->sections->map(function ($section): array {
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
                            'discount_type' => $lineItem->discount_type?->value,
                            'discount_value' => (float) $lineItem->discount_value,
                            'price_tier_applied' => (bool) $lineItem->price_tier_applied,
                            'applied_price_tiers' => $lineItem->priceTiers->pluck('catalog_price_tier_id')->filter()->values()->all(),
                            'subtotal' => (float) $lineItem->base_subtotal,
                            'tax_amount' => (float) $lineItem->base_tax_amount,
                            'total' => (float) $lineItem->base_total,
                            'tax_rate' => (float) $lineItem->tax_rate,
                            'notes' => $lineItem->notes,
                            'sort_order' => $lineItem->sort_order,
                            'taxes' => $lineItem->taxes->map(fn ($tax): array => [
                                'tax_id' => $tax->tax_id,
                                'tax_label' => $tax->tax_label,
                                'tax_rate' => (float) $tax->tax_rate,
                                'inclusive' => (bool) $tax->inclusive,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            $workspace = $invoice->workspace;
            $baseCurrency = $workspace->currency ?? 'USD';

            $sections = Arr::pull($data, 'sections', []);
            $layoutSnapshot = $this->builderLayoutService->normalizeLayoutForStorage($data);

            // Handle currency conversion
            $currency = $data['currency'] ?? $invoice->currency;
            if (empty($currency)) {
                $currency = $baseCurrency;
            }

            $fxRate = $data['fx_rate'] ?? $invoice->fx_rate;
            if ($currency !== $baseCurrency && empty($fxRate)) {
                $fxRate = $this->exchangeRateService->getRate($baseCurrency, $currency);
            } elseif ($currency === $baseCurrency && empty($fxRate)) {
                $fxRate = 1.0;
            }

            $invoice->update([
                'invoice_number' => $data['invoice_number'] ?? $invoice->invoice_number,
                'title' => $data['title'],
                'cover_message' => $data['cover_message'] ?? null,
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'layout_snapshot' => $layoutSnapshot,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['valid_until'] ?? $data['due_date'] ?? $invoice->due_date,
                'currency' => $currency,
                'base_currency' => $baseCurrency,
                'fx_rate' => $fxRate,
            ]);

            $this->syncInvoiceSections($invoice, $sections, $fxRate);

            return $invoice->refresh();
        });
    }

    private function syncInvoiceSections(Invoice $invoice, array $sections, float $fxRate): void
    {
        $invoice->sections()->delete();
        $invoice->lineItems()->delete();

        $baseSubtotal = 0;
        $baseDiscountAmount = 0;
        $baseTaxAmount = 0;

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = $invoice->sections()->create([
                'title' => (string) Arr::get($sectionData, 'title', 'Section'),
                'sort_order' => (int) Arr::get($sectionData, 'sort_order', $sectionIndex),
            ]);

            $lineItems = Arr::get($sectionData, 'line_items', []);

            if (! is_array($lineItems)) {
                continue;
            }

            foreach ($lineItems as $lineItemIndex => $lineItemData) {
                $taxes = Arr::get($lineItemData, 'taxes', []);

                $taxesArray = is_array($taxes) ? collect($taxes)->map(fn ($tax) => [
                    'tax_rate' => (float) Arr::get($tax, 'tax_rate', 0),
                    'inclusive' => (bool) (Arr::get($tax, 'inclusive') ?? Arr::get($tax, 'tax_inclusive', false)),
                ])->values()->all() : [];

                $calculatedTotals = QuotesTaxCalculator::calculateLineItemTotals(
                    (float) Arr::get($lineItemData, 'quantity', 1),
                    (float) Arr::get($lineItemData, 'unit_price', 0),
                    Arr::get($lineItemData, 'discount_type'),
                    (float) Arr::get($lineItemData, 'discount_value', 0),
                    $taxesArray,
                );

                $lineBaseSubtotal = $calculatedTotals['subtotal'];
                $lineBaseTotal = $calculatedTotals['total'];

                $lineBaseTax = collect($calculatedTotals['taxBreakdown'])->sum('tax_amount');

                $quantity = (float) Arr::get($lineItemData, 'quantity', 1);
                $unitPrice = (float) Arr::get($lineItemData, 'unit_price', 0);
                $discountType = Arr::get($lineItemData, 'discount_type');
                $discountValue = (float) Arr::get($lineItemData, 'discount_value', 0);
                
                // Calculate discount based on type
                $lineBaseDiscount = 0;
                if ($discountType === 'percent') {
                    $lineBaseDiscount = ($quantity * $unitPrice) * ($discountValue / 100);
                } elseif ($discountType === 'fixed') {
                    $lineBaseDiscount = $discountValue;
                }

                $invoiceLineItem = $section->lineItems()->create([
                    'invoice_id' => $invoice->id,
                    'catalog_item_id' => Arr::get($lineItemData, 'catalog_item_id'),
                    'catalog_item_variant_id' => Arr::get($lineItemData, 'catalog_item_variant_id'),
                    'name' => (string) Arr::get($lineItemData, 'name', 'Line item'),
                    'description' => Arr::get($lineItemData, 'description'),
                    'quantity' => (float) Arr::get($lineItemData, 'quantity', 1),
                    'unit' => Arr::get($lineItemData, 'unit'),
                    'unit_price' => (float) Arr::get($lineItemData, 'unit_price', 0) * $fxRate,
                    'base_unit_price' => (float) Arr::get($lineItemData, 'unit_price', 0),
                    'tax_rate' => (float) Arr::get($lineItemData, 'tax_rate', 0),
                    'discount_type' => Arr::get($lineItemData, 'discount_type'),
                    'discount_value' => (float) Arr::get($lineItemData, 'discount_value', 0),
                    'price_tier_applied' => (bool) Arr::get($lineItemData, 'price_tier_applied', false),
                    'subtotal' => $lineBaseSubtotal * $fxRate,
                    'base_subtotal' => $lineBaseSubtotal,
                    'tax_amount' => $lineBaseTax * $fxRate,
                    'base_tax_amount' => $lineBaseTax,
                    'total' => $lineBaseTotal * $fxRate,
                    'base_total' => $lineBaseTotal,
                    'sort_order' => (int) Arr::get($lineItemData, 'sort_order', $lineItemIndex),
                    'notes' => Arr::get($lineItemData, 'notes'),
                ]);

                $appliedPriceTierIds = Arr::get($lineItemData, 'applied_price_tiers', []);
                if (is_array($appliedPriceTierIds) && !empty($appliedPriceTierIds)) {
                    $catalogItemPriceTiers = \App\Models\CatalogItemPriceTier::query()
                        ->whereIn('id', $appliedPriceTierIds)
                        ->get();

                    foreach ($catalogItemPriceTiers as $catalogTier) {
                        $invoiceLineItem->priceTiers()->create([
                            'catalog_price_tier_id' => $catalogTier->id,
                            'variant_id' => $catalogTier->variant_id,
                            'min_quantity' => $catalogTier->min_quantity,
                            'max_quantity' => $catalogTier->max_quantity,
                            'pricing_type' => $catalogTier->pricing_type->value,
                            'value' => $catalogTier->value,
                        ]);
                    }
                }

                foreach ($taxes as $index => $taxData) {
                    $inclusiveValue = Arr::get($taxData, 'inclusive') ?? Arr::get($taxData, 'tax_inclusive', false);
                    $taxBreakdown = $calculatedTotals['taxBreakdown'][$index] ?? null;
                    $baseTaxAmount = $taxBreakdown['tax_amount'] ?? 0;

                    InvoiceLineItemTax::query()->create([
                        'invoice_line_item_id' => $invoiceLineItem->id,
                        'tax_id' => Arr::get($taxData, 'tax_id'),
                        'tax_label' => (string) Arr::get($taxData, 'tax_label', 'Tax'),
                        'tax_rate' => (float) Arr::get($taxData, 'tax_rate', 0),
                        'inclusive' => (bool) $inclusiveValue,
                        'tax_amount' => $baseTaxAmount * $fxRate,
                        'base_tax_amount' => $baseTaxAmount,
                    ]);
                }

                $baseSubtotal += $lineBaseSubtotal;
                $baseDiscountAmount += $lineBaseDiscount;
                $baseTaxAmount += $lineBaseTax;
            }
        }

        $baseTotal = $baseSubtotal + $baseTaxAmount - $baseDiscountAmount;

        $invoiceSubtotal = $baseSubtotal * $fxRate;
        $invoiceDiscountAmount = $baseDiscountAmount * $fxRate;
        $invoiceTaxAmount = $baseTaxAmount * $fxRate;
        $invoiceTotal = $baseTotal * $fxRate;

        $invoice->update([
            'subtotal' => $invoiceSubtotal,
            'discount_amount' => $invoiceDiscountAmount,
            'tax_amount' => $invoiceTaxAmount,
            'total' => $invoiceTotal,
            'base_subtotal' => $baseSubtotal,
            'base_discount_amount' => $baseDiscountAmount,
            'base_tax_amount' => $baseTaxAmount,
            'base_total' => $baseTotal,
        ]);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{processed:int,skipped:int,missing:int,skipped_details:array<int, array{id:int,status:string,reason:string}>}
     */
    public function bulkAction(Workspace $workspace, array $ids, string $action): array
    {
        $invoices = Invoice::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids)
            ->get(['id', 'workspace_id', 'status']);

        $eligibleIds = [];
        $skipped = [];

        foreach ($invoices as $invoice) {
            $status = $invoice->status instanceof InvoiceStatus
                ? $invoice->status
                : InvoiceStatus::from($invoice->status);

            $canProceed = match ($action) {
                'delete' => $status->canBeDeleted(),
                'archive' => $status->canBeArchived(),
                default => false,
            };

            if ($canProceed) {
                $eligibleIds[] = $invoice->id;
            } else {
                $skipped[] = [
                    'id' => $invoice->id,
                    'status' => $status->value,
                    'reason' => "Action '{$action}' not permitted for status {$status->value}.",
                ];
            }
        }

        if ($eligibleIds !== []) {
            Invoice::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('id', $eligibleIds)
                ->delete();

            if ($action === 'archive') {
                $now = now();
                $userId = auth()->id();

                $activities = array_map(
                    fn (int $invoiceId): array => [
                        'invoice_id' => $invoiceId,
                        'workspace_id' => $workspace->id,
                        'user_id' => $userId,
                        'type' => InvoiceActivityType::Voided->value,
                        'description' => 'Invoice archived',
                        'metadata' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $eligibleIds,
                );

                InvoiceActivity::query()->insert($activities);
            }
        }

        $missingCount = max(0, count($ids) - $invoices->count());

        return [
            'processed' => count($eligibleIds),
            'skipped' => count($skipped),
            'missing' => $missingCount,
            'skipped_details' => $skipped,
        ];
    }
}
