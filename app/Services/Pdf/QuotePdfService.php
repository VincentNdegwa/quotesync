<?php

namespace App\Services\Pdf;

use App\Models\Quote;
use App\Support\Quotes\QuoteLayout;
use App\Support\WorkspaceBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    public function __construct(private readonly WorkspaceBranding $workspaceBranding) {}

    public function generate(Quote $quote): string
    {
        $quote->load([
            'client',
            'sections.lineItems.taxes',
            'sections.lineItems.catalogItem',
            'workspace.owner',
            'workspace.settings',
        ]);

        $layout = QuoteLayout::normalize($quote->layout_snapshot);
        $branding = $this->workspaceBranding->forWorkspace($quote->workspace);

        $html = view('pdf.quotes.index', [
            'quote' => $quote,
            'theme' => $layout['theme'],
            'blocks' => $layout['blocks'],
            'branding' => $branding,
            'signatureDataUri' => $this->resolveSignatureDataUri($quote),
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = "quotes/{$quote->id}_{$quote->quote_uuid}.pdf";

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    private function resolveSignatureDataUri(Quote $quote): ?string
    {
        $signaturePath = $quote->signature_path;

        if (! is_string($signaturePath) || $signaturePath === '') {
            return null;
        }

        if (Storage::exists($signaturePath)) {
            $mime = mime_content_type(Storage::path($signaturePath)) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(Storage::get($signaturePath));
        }

        if (Storage::disk('public')->exists($signaturePath)) {
            $mime = mime_content_type(Storage::disk('public')->path($signaturePath)) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($signaturePath));
        }

        if (file_exists($signaturePath)) {
            $mime = mime_content_type($signaturePath) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($signaturePath));
        }

        return null;
    }
}
