<?php

namespace App\Services\RecurringInvoices;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\RecurringInvoice;
use App\Services\Invoices\InvoiceNumberingService;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    public function __construct(
        private InvoiceNumberingService $numberingService
    ) {}

    public function generateNextInvoice(RecurringInvoice $recurringInvoice): ?Invoice
    {
        if (! $recurringInvoice->is_active) {
            return null;
        }

        if ($recurringInvoice->end_date && $recurringInvoice->next_invoice_date > $recurringInvoice->end_date) {
            $recurringInvoice->update(['is_active' => false]);

            return null;
        }

        return DB::transaction(function () use ($recurringInvoice): ?Invoice {
            $invoice = Invoice::query()->create([
                'workspace_id' => $recurringInvoice->workspace_id,
                'client_id' => $recurringInvoice->client_id,
                'recurring_invoice_id' => $recurringInvoice->id,
                'invoice_number' => $this->numberingService->generateNextNumber($recurringInvoice->workspace),
                'title' => $recurringInvoice->name,
                'currency' => $recurringInvoice->currency,
                'subtotal' => $recurringInvoice->subtotal,
                'tax_amount' => $recurringInvoice->tax_amount,
                'discount_amount' => $recurringInvoice->discount_amount,
                'total' => $recurringInvoice->total,
                'paid_amount' => 0,
                'status' => 'draft',
                'issue_date' => $recurringInvoice->next_invoice_date,
                'due_date' => $recurringInvoice->next_invoice_date->copy()->addDays(30),
                'created_by' => $recurringInvoice->created_by,
                'layout_snapshot' => $recurringInvoice->layout_snapshot,
            ]);

            foreach ($recurringInvoice->sections as $section) {
                foreach ($section['line_items'] ?? [] as $item) {
                    InvoiceLineItem::query()->create([
                        'invoice_id' => $invoice->id,
                        'catalog_item_id' => $item['catalog_item_id'] ?? null,
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'total' => $item['total'],
                        'sort_order' => $item['sort_order'] ?? 0,
                    ]);
                }
            }

            $this->updateNextInvoiceDate($recurringInvoice);

            return $invoice->fresh();
        });
    }

    private function updateNextInvoiceDate(RecurringInvoice $recurringInvoice): void
    {
        $nextDate = match ($recurringInvoice->frequency) {
            'daily' => $recurringInvoice->next_invoice_date->addDays($recurringInvoice->interval),
            'weekly' => $recurringInvoice->next_invoice_date->addWeeks($recurringInvoice->interval),
            'monthly' => $recurringInvoice->next_invoice_date->addMonths($recurringInvoice->interval),
            'quarterly' => $recurringInvoice->next_invoice_date->addMonths($recurringInvoice->interval * 3),
            'yearly' => $recurringInvoice->next_invoice_date->addYears($recurringInvoice->interval),
            default => $recurringInvoice->next_invoice_date->addMonth(),
        };

        $recurringInvoice->update(['next_invoice_date' => $nextDate]);
    }

    public function pause(RecurringInvoice $recurringInvoice): RecurringInvoice
    {
        $recurringInvoice->update(['is_active' => false]);

        return $recurringInvoice->fresh();
    }

    public function resume(RecurringInvoice $recurringInvoice): RecurringInvoice
    {
        $recurringInvoice->update(['is_active' => true]);

        return $recurringInvoice->fresh();
    }
}
