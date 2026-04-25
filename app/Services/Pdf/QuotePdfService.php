<?php

namespace App\Services\Pdf;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    public function generate(Quote $quote): string
    {
        $quote->load(['client', 'sections.lineItems', 'workspace.owner']);

        $html = $this->renderHtml($quote);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = "quotes/{$quote->id}_{$quote->quote_uuid}.pdf";
        
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    protected function renderHtml(Quote $quote): string
    {
        $layout = $quote->layout_snapshot ?? $this->getDefaultLayout();
        $theme = $layout['theme'] ?? [];
        $blocks = $layout['blocks'] ?? [];

        $styles = $this->generateStyles($theme);
        $blockData = $this->prepareBlockData($blocks, $quote);

        return view('pdf.quotes.index', [
            'quote' => $quote,
            'styles' => $styles,
            'blocks' => $blocks,
            'blockData' => $blockData,
        ])->render();
    }

    protected function generateStyles(array $theme): string
    {
        $primaryColor = $theme['primaryColor'] ?? '#2563EB';
        $accentColor = $theme['accentColor'] ?? '#F59E0B';
        $backgroundColor = $theme['backgroundColor'] ?? '#FFFFFF';
        $fontFamily = $theme['fontFamily'] ?? 'inter';
        $fontSize = $theme['fontSize'] ?? 'md';
        $borderRadius = $theme['borderRadius'] ?? 'md';

        $fontSizes = [
            'sm' => '14px',
            'md' => '16px',
            'lg' => '18px',
        ];

        $borderRadii = [
            'none' => '0',
            'sm' => '4px',
            'md' => '8px',
            'lg' => '12px',
        ];

        $baseFontSize = $fontSizes[$fontSize] ?? '16px';
        $baseBorderRadius = $borderRadii[$borderRadius] ?? '8px';

        return <<<CSS
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: {$baseFontSize};
            color: #1f2937;
            background-color: {$backgroundColor};
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        .block {
            margin-bottom: 24px;
        }

        .header {
            background-color: {$accentColor};
            padding: 24px;
            border-radius: {$baseBorderRadius};
            border: 1px solid #ffffff;
        }

        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .header-meta {
            color: #ffffff;
            font-size: 14px;
        }

        .from-to {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .from-to-section {
            flex: 1;
        }

        .from-to-label {
            font-weight: bold;
            color: {$primaryColor};
            margin-bottom: 8px;
        }

        .cover-message {
            padding: 20px;
            background-color: #f9fafb;
            border-radius: {$baseBorderRadius};
        }

        .cover-message-label {
            font-weight: bold;
            color: {$accentColor};
            margin-bottom: 12px;
        }

        .line-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .line-items-table th {
            background-color: {$accentColor};
            color: #ffffff;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        .line-items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .line-items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .totals {
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 8px 0;
        }

        .total-label {
            margin-right: 20px;
            color: #6b7280;
        }

        .total-value {
            font-weight: bold;
            color: #1f2937;
        }

        .total-final {
            font-size: 20px;
            color: {$primaryColor};
            border-top: 2px solid {$primaryColor};
            padding-top: 12px;
            margin-top: 8px;
        }

        .payment-terms {
            padding: 20px;
            background-color: #f9fafb;
            border-radius: {$baseBorderRadius};
        }

        .payment-terms-label {
            font-weight: bold;
            color: {$accentColor};
            margin-bottom: 12px;
        }

        .terms {
            padding: 20px;
            background-color: #f9fafb;
            border-radius: {$baseBorderRadius};
        }

        .terms-label {
            font-weight: bold;
            color: {$accentColor};
            margin-bottom: 12px;
        }

        .signature {
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: {$baseBorderRadius};
            text-align: center;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 12px;
        }

        .signature-image {
            max-width: 200px;
            max-height: 100px;
            margin: 12px auto;
        }

        .signature-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        CSS;
    }

    protected function prepareBlockData(array $blocks, Quote $quote): array
    {
        $data = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $config = $block['config'] ?? [];

            switch ($type) {
                case 'header':
                    $data['header'] = $this->prepareHeaderData($config, $quote);
                    break;
                case 'from_to':
                    $data['from_to'] = $this->prepareFromToData($config, $quote);
                    break;
                case 'cover_message':
                    $data['cover_message'] = $this->prepareCoverMessageData($config, $quote);
                    break;
                case 'line_items':
                    $data['line_items'] = $this->prepareLineItemsData($config, $quote);
                    break;
                case 'totals':
                    $data['totals'] = $this->prepareTotalsData($config, $quote);
                    break;
                case 'payment_terms':
                    $data['payment_terms'] = $this->preparePaymentTermsData($config, $quote);
                    break;
                case 'terms':
                    $data['terms'] = $this->prepareTermsData($config, $quote);
                    break;
                case 'signature':
                    $data['signature'] = $this->prepareSignatureData($config, $quote);
                    break;
            }
        }

        return $data;
    }

    protected function prepareHeaderData(array $config, Quote $quote): array
    {
        $showQuoteNumber = $config['showQuoteNumber'] ?? true;
        $showIssueDate = $config['showIssueDate'] ?? true;
        $showValidUntil = $config['showValidUntil'] ?? true;

        $quoteNumber = $quote->number ?? '';
        $issueDate = $quote->created_at ? $quote->created_at->format('M d, Y') : '';
        $validUntil = $quote->valid_until ? $quote->valid_until->format('M d, Y') : '';

        $metaHtml = '';
        if ($showQuoteNumber) {
            $metaHtml .= "Quote #: {$quoteNumber}<br>";
        }
        if ($showIssueDate) {
            $metaHtml .= "Issue Date: {$issueDate}<br>";
        }
        if ($showValidUntil) {
            $metaHtml .= "Valid Until: {$validUntil}";
        }

        return ['metaHtml' => $metaHtml];
    }

    protected function prepareFromToData(array $config, Quote $quote): array
    {
        $showCompanyAddress = $config['showCompanyAddress'] ?? true;
        $showCompanyEmail = $config['showCompanyEmail'] ?? true;
        $showCompanyPhone = $config['showCompanyPhone'] ?? true;
        $showClientAddress = $config['showClientAddress'] ?? true;
        $showClientEmail = $config['showClientEmail'] ?? true;

        $workspace = $quote->workspace;
        $client = $quote->client;

        $fromHtml = '';
        if ($showCompanyAddress) {
            $fromHtml .= "<div>{$workspace->owner->name}</div>";
        }
        if ($showCompanyEmail) {
            $fromHtml .= "<div>{$workspace->owner->email}</div>";
        }
        if ($showCompanyPhone) {
            $phone = $workspace->owner->phone ?? '';
            $fromHtml .= "<div>{$phone}</div>";
        }

        $toHtml = '';
        if ($client) {
            if ($showClientAddress) {
                $toHtml .= "<div>{$client->company_name}</div>";
            }
            if ($showClientEmail) {
                $email = $client->email ?? '';
                $toHtml .= "<div>{$email}</div>";
            }
        }

        return ['fromHtml' => $fromHtml, 'toHtml' => $toHtml];
    }

    protected function prepareCoverMessageData(array $config, Quote $quote): array
    {
        $showLabel = $config['showLabel'] ?? true;
        $labelText = $config['labelText'] ?? 'Introduction';
        $contextText = $config['contextText'] ?? $quote->cover_message;

        $labelHtml = '';
        if ($showLabel) {
            $labelHtml = "<div class=\"cover-message-label\">{$labelText}</div>";
        }

        $contentHtml = $contextText ?? '';

        return ['labelHtml' => $labelHtml, 'contentHtml' => $contentHtml];
    }

    protected function prepareLineItemsData(array $config, Quote $quote): array
    {
        return [
            'showSectionTitles' => $config['showSectionTitles'] ?? true,
            'showItemDescription' => $config['showItemDescription'] ?? true,
            'showUnitPrice' => $config['showUnitPrice'] ?? true,
            'showQuantity' => $config['showQuantity'] ?? true,
            'showLineTotal' => $config['showLineTotal'] ?? true,
        ];
    }

    protected function prepareTotalsData(array $config, Quote $quote): array
    {
        return [
            'showSubtotal' => $config['showSubtotal'] ?? true,
            'showGlobalDiscount' => $config['showGlobalDiscount'] ?? true,
            'showTaxTotal' => $config['showTaxTotal'] ?? true,
        ];
    }

    protected function preparePaymentTermsData(array $config, Quote $quote): array
    {
        return [
            'labelText' => $config['labelText'] ?? 'Payment Schedule',
            'contextText' => $config['contextText'] ?? '',
        ];
    }

    protected function prepareTermsData(array $config, Quote $quote): array
    {
        $labelText = $config['labelText'] ?? 'Terms & Conditions';
        $contextText = $config['contextText'] ?? $quote->terms;
        $contentHtml = $contextText ?? '';

        return ['labelText' => $labelText, 'contentHtml' => $contentHtml];
    }

    protected function prepareSignatureData(array $config, Quote $quote): array
    {
        return [
            'showContextText' => $config['showContextText'] ?? true,
            'contextText' => $config['contextText'] ?? 'By signing, you agree to the terms and conditions above.',
        ];
    }

    protected function getDefaultLayout(): array
    {
        return [
            'version' => 1,
            'theme' => [
                'primaryColor' => '#2563EB',
                'accentColor' => '#F59E0B',
                'backgroundColor' => '#FFFFFF',
                'fontFamily' => 'inter',
                'fontSize' => 'md',
                'borderRadius' => 'md',
            ],
            'blocks' => [],
        ];
    }
}
