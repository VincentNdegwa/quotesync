<?php

namespace App\Jobs;

use App\Models\CreditNote;
use App\Services\Pdf\CreditNotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCreditNotePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public CreditNote $creditNote
    ) {}

    public function handle(CreditNotePdfService $pdfService): void
    {
        $pdfPath = $pdfService->generate($this->creditNote);

        $this->creditNote->update([
            'pdf_url' => $pdfPath,
        ]);
    }
}
