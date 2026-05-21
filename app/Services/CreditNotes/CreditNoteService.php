<?php

namespace App\Services\CreditNotes;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function generateFromInvoice(Invoice $invoice, string $reason, float $amount): CreditNote
    {
        abort_if($invoice->workspace_id !== auth()->user()?->current_workspace_id, 403);

        return DB::transaction(function () use ($invoice, $reason, $amount): CreditNote {
            $workspace = $invoice->workspace;

            $creditNoteNumber = $this->generateCreditNoteNumber($workspace);

            $creditNote = CreditNote::query()->create([
                'workspace_id' => $workspace->id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'created_by' => auth()->id(),
                'credit_note_number' => $creditNoteNumber,
                'title' => "Credit Note for Invoice {$invoice->invoice_number}",
                'reason' => $reason,
                'currency' => $invoice->currency,
                'amount' => $amount,
                'tax_amount' => 0,
                'total' => $amount,
                'issue_date' => now(),
                'status' => 'draft',
            ]);

            InvoiceActivity::query()->create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => auth()->id(),
                'type' => 'updated',
                'description' => "Credit note {$creditNoteNumber} generated for {$amount}",
                'metadata' => ['credit_note_id' => $creditNote->id, 'amount' => $amount],
            ]);

            return $creditNote->fresh();
        });
    }

    private function generateCreditNoteNumber(Workspace $workspace): string
    {
        $prefix = 'CN-';
        $lastCreditNote = CreditNote::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->first();

        if ($lastCreditNote) {
            $lastNumber = (int) str_replace($prefix, '', $lastCreditNote->credit_note_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function finalize(CreditNote $creditNote): CreditNote
    {
        abort_if($creditNote->workspace_id !== auth()->user()?->current_workspace_id, 403);

        $creditNote->update(['status' => 'issued']);

        return $creditNote->fresh();
    }
}
