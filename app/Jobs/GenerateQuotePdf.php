<?php

namespace App\Jobs;

use App\Models\Quote;
use App\Services\Pdf\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQuotePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Quote $quote
    ) {}

    public function handle(QuotePdfService $pdfService): void
    {
        $pdfPath = $pdfService->generate($this->quote);

        $this->quote->update([
            'pdf_url' => $pdfPath,
        ]);
    }
}
