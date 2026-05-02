<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\InvoiceLineItem;
use App\Models\InvoiceLineItemTax;
use App\Models\Quote;
use App\Services\Invoices\InvoiceNumberingService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private InvoiceNumberingService $invoiceNumberingService,
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
                'balance_due' => $quote->total,
                'status' => 'draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'created_by' => $createdBy,
            ]);

            foreach ($quote->sections as $section) {
                foreach ($section->lineItems as $lineItem) {
                    $invoiceLineItem = InvoiceLineItem::query()->create([
                        'invoice_id' => $invoice->id,
                        'catalog_item_id' => $lineItem->catalog_item_id,
                        'name' => $lineItem->name,
                        'description' => $lineItem->description,
                        'quantity' => $lineItem->quantity,
                        'unit' => $lineItem->unit,
                        'unit_price' => $lineItem->unit_price,
                        'base_unit_price' => $lineItem->base_unit_price,
                        'tax_rate' => $lineItem->tax_rate,
                        'discount_percent' => $lineItem->discount_percent,
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
                        $invoiceLineItemTax = new InvoiceLineItemTax();
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
            $invoice->load(['lineItems.taxes', 'workspace']);

            $workspace = $invoice->workspace;

            $newInvoice = $invoice->replicate();
            $newInvoice->invoice_number = $this->invoiceNumberingService->generateNextNumber($workspace);
            $newInvoice->title = $invoice->title . ' ' . $suffix;
            $newInvoice->status = 'draft';
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now()->addDays(30);
            $newInvoice->paid_amount = 0;
            $newInvoice->balance_due = $invoice->total;
            $newInvoice->sent_at = null;
            $newInvoice->paid_date = null;
            $newInvoice->invoice_uuid = (string) \Illuminate\Support\Str::uuid();
            $newInvoice->save();

            foreach ($invoice->lineItems as $lineItem) {
                $newLineItem = $lineItem->replicate();
                $newLineItem->invoice_id = $newInvoice->id;
                $newLineItem->save();

                foreach ($lineItem->taxes as $tax) {
                    $newTax = $tax->replicate();
                    $newTax->invoice_line_item_id = $newLineItem->id;
                    $newTax->save();
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
            'lineItems.taxes',
            'client',
        ]);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
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
            'valid_until' => $invoice->due_date?->toDateString(), // Map to valid_until for builder
            'cover_message' => $invoice->cover_message,
            'terms' => $invoice->terms,
            'notes' => $invoice->notes,
            'quote_id' => $invoice->quote_id,
            'layout_snapshot' => $invoice->layout_snapshot,
            'subtotal' => $invoice->base_subtotal,
            'discount_amount' => $invoice->base_discount_amount,
            'tax_amount' => $invoice->base_tax_amount,
            'total' => $invoice->base_total,
            'layout' => $invoice->layout_snapshot,
            'line_items' => $invoice->lineItems->map(function ($lineItem): array {
                return [
                    'id' => $lineItem->id,
                    'catalog_item_id' => $lineItem->catalog_item_id,
                    'name' => $lineItem->name,
                    'description' => $lineItem->description,
                    'quantity' => (float) $lineItem->quantity,
                    'unit' => $lineItem->unit,
                    'unit_price' => (float) $lineItem->base_unit_price,
                    'discount_percent' => (float) $lineItem->discount_percent,
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
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->update([
                'invoice_number' => $data['invoice_number'] ?? $invoice->invoice_number,
                'title' => $data['title'],
                'cover_message' => $data['cover_message'] ?? null,
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'layout_snapshot' => $data['layout_snapshot'] ?? null,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['valid_until'] ?? $data['due_date'] ?? $invoice->due_date, // Map valid_until to due_date
                'subtotal' => $data['subtotal'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'total' => $data['total'] ?? 0,
            ]);

            // Sync line items
            $invoice->lineItems()->delete();

            foreach ($data['line_items'] ?? [] as $item) {
                $invoiceLineItem = InvoiceLineItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'catalog_item_id' => $item['catalog_item_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => $item['total'] ?? 0,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);

                foreach ($item['taxes'] ?? [] as $tax) {
                    InvoiceLineItemTax::query()->create([
                        'invoice_line_item_id' => $invoiceLineItem->id,
                        'tax_id' => $tax['tax_id'] ?? null,
                        'tax_label' => $tax['tax_label'],
                        'tax_rate' => $tax['tax_rate'],
                        'inclusive' => $tax['inclusive'] ?? false,
                    ]);
                }
            }

            return $invoice->refresh();
        });
    }
}
