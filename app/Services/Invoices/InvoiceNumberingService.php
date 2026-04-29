<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\Workspace;

class InvoiceNumberingService
{
    public function generateNextNumber(Workspace $workspace): string
    {
        $prefix = $workspace->settings->get('invoice_prefix', 'INV');
        $sequence = (int) $workspace->settings->get('invoice_number_sequence', 1);
        $resetYearly = (bool) $workspace->settings->get('invoice_number_reset_yearly', true);

        if ($resetYearly) {
            $currentYear = now()->year;
            $lastNumber = Invoice::query()
                ->where('workspace_id', $workspace->id)
                ->whereYear('created_at', $currentYear)
                ->orderByDesc('id')
                ->value('invoice_number');

            if ($lastNumber) {
                $lastSequence = (int) str_replace($prefix, '', $lastNumber);
                $sequence = $lastSequence + 1;
            }
        } else {
            $lastNumber = Invoice::query()
                ->where('workspace_id', $workspace->id)
                ->orderByDesc('id')
                ->value('invoice_number');

            if ($lastNumber) {
                $lastSequence = (int) str_replace($prefix, '', $lastNumber);
                $sequence = $lastSequence + 1;
            }
        }

        $workspace->settings->put('invoice_number_sequence', $sequence);

        return $prefix.$sequence;
    }
}
