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
        
        // Get workspace settings for quotes (not quotes_invoices group)
        $settingsService = app(\App\Services\WorkspaceSettings\WorkspaceSettingsService::class);
        $quoteSettings = collect($settingsService->groupForFrontend($quote->workspace, 'quotes')['fields'] ?? [])
            ->keyBy('key')
            ->map(fn ($field) => $field['value'] ?? $field['default'] ?? null)
            ->toArray();

        $html = view('pdf.quotes.index', [
            'quote' => $quote,
            'theme' => $layout['theme'],
            'blocks' => $layout['blocks'],
            'branding' => $branding,
            'signatureDataUri' => $this->resolveSignatureDataUri($quote),
            'settings' => $quoteSettings,
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
        $signatureUrl = $quote->signature_url;

        if (! is_string($signatureUrl) || $signatureUrl === '') {
            return null;
        }

        if (!str_starts_with($signatureUrl, 'http')) {
            return null;
        }

        $relativePath = str_replace(Storage::url(''), '', $signatureUrl);
        if (Storage::disk('public')->exists($relativePath)) {
            $mime = mime_content_type(Storage::disk('public')->path($relativePath)) ?: 'image/png';
            return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($relativePath));
        }

        return null;
    }
}
