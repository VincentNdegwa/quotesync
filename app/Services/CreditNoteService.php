<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteLineItem;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Workspace;
use App\Services\CreditNotes\CreditNoteNumberingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(
        private CreditNoteNumberingService $creditNoteNumberingService,
    ) {}

    public function create(array $data, Workspace $workspace, int $userId): CreditNote
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);
        abort_unless($invoice->workspace_id === $workspace->id, 404);

        $client = Client::findOrFail($data['client_id']);
        abort_unless($client->workspace_id === $workspace->id, 404);

        $currency = $invoice->currency ?? $workspace->currency;
        $baseCurrency = $workspace->currency;
        $fxRate = ($currency !== $baseCurrency) ? ($invoice->fx_rate ?? 1.0) : 1.0;
        $creditNoteNumber = $this->creditNoteNumberingService->generateNextNumber($workspace);

        // Pre-fetch invoice line items to avoid N+1 queries
        $invoiceLineItems = InvoiceLineItem::where('invoice_id', $invoice->id)
            ->get()
            ->keyBy('id');

        $totals = $this->calculateTotals($data['type'], $data, $invoice, $invoiceLineItems, $fxRate);

        return DB::transaction(function () use ($data, $workspace, $invoice, $client, $creditNoteNumber, $userId, $currency, $baseCurrency, $fxRate, $totals, $invoiceLineItems) {
            $creditNote = CreditNote::create([
                'workspace_id' => $workspace->id,
                'invoice_id' => $data['invoice_id'],
                'client_id' => $data['client_id'],
                'created_by' => $userId,
                'credit_note_number' => $creditNoteNumber,
                'title' => $data['title'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'currency' => $currency,
                'base_currency' => $baseCurrency,
                'fx_rate' => $fxRate,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'base_subtotal' => $totals['base_subtotal'],
                'base_tax_amount' => $totals['base_tax_amount'],
                'base_total' => $totals['base_total'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'draft',
            ]);

            if (isset($data['line_items']) && $data['type'] === 'line_items') {
                foreach ($data['line_items'] as $itemData) {
                    $invoiceLineItem = $invoiceLineItems->get($itemData['id']);
                    abort_unless($invoiceLineItem, 404);

                    $itemTotals = $this->calculateLineItemTotals(
                        $itemData['unit_price'],
                        $itemData['credit_quantity'],
                        $invoiceLineItem,
                        $fxRate
                    );

                    CreditNoteLineItem::create([
                        'credit_note_id' => $creditNote->id,
                        'name' => $invoiceLineItem->name,
                        'description' => $invoiceLineItem->description,
                        'quantity' => $itemData['credit_quantity'],
                        'unit' => $invoiceLineItem->unit,
                        'unit_price' => $itemData['unit_price'],
                        'base_unit_price' => $itemTotals['base_unit_price'],
                        'tax_amount' => $itemTotals['tax_amount'],
                        'base_tax_amount' => $itemTotals['base_tax_amount'],
                        'subtotal' => $itemTotals['subtotal'],
                        'base_subtotal' => $itemTotals['base_subtotal'],
                        'total' => $itemTotals['total'],
                        'base_total' => $itemTotals['base_total'],
                    ]);
                }
            }

            return $creditNote;
        });
    }

    private function calculateTotals(string $type, array $data, Invoice $invoice, Collection $invoiceLineItems, float $fxRate): array
    {
        $subtotal = 0;
        $taxAmount = 0;
        $total = 0;
        $baseSubtotal = 0;
        $baseTaxAmount = 0;
        $baseTotal = 0;

        if ($type === 'full') {
            $subtotal = $invoice->subtotal;
            $taxAmount = $invoice->tax_amount;
            $total = $invoice->total;
            $baseSubtotal = $invoice->base_subtotal ?? ($subtotal / $fxRate);
            $baseTaxAmount = $invoice->base_tax_amount ?? ($taxAmount / $fxRate);
            $baseTotal = $invoice->base_total ?? ($total / $fxRate);
        } elseif ($type === 'partial') {
            $total = $data['partial_amount'];
            $taxRate = $this->getTaxRate($invoice->total, $invoice->tax_amount);
            $taxAmount = $total * $taxRate;
            $subtotal = $total - $taxAmount;
            $baseTotal = $total / $fxRate;
            $baseTaxAmount = $taxAmount / $fxRate;
            $baseSubtotal = $subtotal / $fxRate;
        } elseif ($type === 'line_items' && isset($data['line_items'])) {
            foreach ($data['line_items'] as $itemData) {
                $invoiceLineItem = $invoiceLineItems->get($itemData['id']);
                if (!$invoiceLineItem) continue;

                $itemTotals = $this->calculateLineItemTotals(
                    $itemData['unit_price'],
                    $itemData['credit_quantity'],
                    $invoiceLineItem,
                    $fxRate
                );

                $subtotal += $itemTotals['subtotal'];
                $taxAmount += $itemTotals['tax_amount'];
                $total += $itemTotals['total'];
                $baseSubtotal += $itemTotals['base_subtotal'];
                $baseTaxAmount += $itemTotals['base_tax_amount'];
                $baseTotal += $itemTotals['base_total'];
            }
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'base_subtotal' => $baseSubtotal,
            'base_tax_amount' => $baseTaxAmount,
            'base_total' => $baseTotal,
        ];
    }

    private function calculateLineItemTotals(float $unitPrice, float $creditQuantity, InvoiceLineItem $invoiceLineItem, float $fxRate): array
    {
        $subtotal = $unitPrice * $creditQuantity;
        $taxRate = $this->getTaxRate($invoiceLineItem->total, $invoiceLineItem->tax_amount);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'base_unit_price' => $unitPrice / $fxRate,
            'base_subtotal' => $subtotal / $fxRate,
            'base_tax_amount' => $taxAmount / $fxRate,
            'base_total' => $total / $fxRate,
        ];
    }

    private function getTaxRate(float $total, float $taxAmount): float
    {
        return $total > 0 ? ($taxAmount / $total) : 0;
    }

    public function update(CreditNote $creditNote, array $data): void
    {
        $invoice = $creditNote->invoice;
        $fxRate = $creditNote->fx_rate ?? 1.0;

        // Pre-fetch invoice line items to avoid N+1 queries
        $invoiceLineItems = InvoiceLineItem::where('invoice_id', $invoice->id)
            ->get()
            ->keyBy('id');

        $totals = $this->calculateTotals($data['type'], $data, $invoice, $invoiceLineItems, $fxRate);

        DB::transaction(function () use ($creditNote, $data, $fxRate, $totals, $invoiceLineItems, $invoice) {
            $creditNote->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'base_subtotal' => $totals['base_subtotal'],
                'base_tax_amount' => $totals['base_tax_amount'],
                'base_total' => $totals['base_total'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
            ]);

            CreditNoteLineItem::where('credit_note_id', $creditNote->id)->delete();

            if (isset($data['line_items']) && $data['type'] === 'line_items') {
                foreach ($data['line_items'] as $itemData) {
                    $invoiceLineItem = $invoiceLineItems->get($itemData['id']);
                    abort_unless($invoiceLineItem, 404);

                    $itemTotals = $this->calculateLineItemTotals(
                        $itemData['unit_price'],
                        $itemData['credit_quantity'],
                        $invoiceLineItem,
                        $fxRate
                    );

                    CreditNoteLineItem::create([
                        'credit_note_id' => $creditNote->id,
                        'name' => $invoiceLineItem->name,
                        'description' => $invoiceLineItem->description,
                        'quantity' => $itemData['credit_quantity'],
                        'unit' => $invoiceLineItem->unit,
                        'unit_price' => $itemData['unit_price'],
                        'base_unit_price' => $itemTotals['base_unit_price'],
                        'tax_amount' => $itemTotals['tax_amount'],
                        'base_tax_amount' => $itemTotals['base_tax_amount'],
                        'subtotal' => $itemTotals['subtotal'],
                        'base_subtotal' => $itemTotals['base_subtotal'],
                        'total' => $itemTotals['total'],
                        'base_total' => $itemTotals['base_total'],
                    ]);
                }
            }
        });
    }

    public function issue(CreditNote $creditNote): void
    {
        DB::transaction(function () use ($creditNote) {
            $creditNote->update([
                'status' => 'issued',
                'issued_at' => now(),
            ]);
        });
    }

    public function apply(CreditNote $creditNote): void
    {
        DB::transaction(function () use ($creditNote) {
            $creditNote->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            if ($creditNote->invoice) {
                $invoice = $creditNote->invoice;
                $invoice->updateQuietly([
                    'amount_credited' => $invoice->amount_credited + $creditNote->total,
                ]);
            }
        });
    }

    public function void(CreditNote $creditNote, string $voidReason): void
    {
        DB::transaction(function () use ($creditNote, $voidReason) {
            $wasApplied = $creditNote->status === 'applied' && $creditNote->applied_at !== null;

            $creditNote->update([
                'status' => 'voided',
                'voided_at' => now(),
                'void_reason' => $voidReason,
            ]);

            if ($creditNote->invoice && $wasApplied) {
                $invoice = $creditNote->invoice;
                $invoice->updateQuietly([
                    'amount_credited' => max(0, $invoice->amount_credited - $creditNote->total),
                ]);
            }
        });
    }
}
