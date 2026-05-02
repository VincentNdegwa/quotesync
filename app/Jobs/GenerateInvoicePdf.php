<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Pdf\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function handle(InvoicePdfService $pdfService): void
    {
        $pdfPath = $pdfService->generate($this->invoice);

        $this->invoice->update([
            'pdf_url' => $pdfPath,
        ]);
    }
}
