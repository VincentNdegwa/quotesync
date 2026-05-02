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
}
