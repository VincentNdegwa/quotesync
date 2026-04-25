<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! $styles !!}
    </style>
</head>
<body>
    <div class="container">
        @foreach($blocks as $block)
            @if($block['visible'] ?? true)
                @switch($block['type'])
                    @case('header')
                        @include('pdf.quotes.blocks.header', [
                            'quote' => $quote,
                            'metaHtml' => $blockData['header']['metaHtml'] ?? '',
                        ])
                    @break

                    @case('from_to')
                        @include('pdf.quotes.blocks.from_to', [
                            'fromHtml' => $blockData['from_to']['fromHtml'] ?? '',
                            'toHtml' => $blockData['from_to']['toHtml'] ?? '',
                        ])
                    @break

                    @case('cover_message')
                        @include('pdf.quotes.blocks.cover_message', [
                            'labelHtml' => $blockData['cover_message']['labelHtml'] ?? '',
                            'contentHtml' => $blockData['cover_message']['contentHtml'] ?? '',
                        ])
                    @break

                    @case('line_items')
                        @include('pdf.quotes.blocks.line_items', [
                            'quote' => $quote,
                            'showSectionTitles' => $blockData['line_items']['showSectionTitles'] ?? true,
                            'showItemDescription' => $blockData['line_items']['showItemDescription'] ?? true,
                            'showUnitPrice' => $blockData['line_items']['showUnitPrice'] ?? true,
                            'showQuantity' => $blockData['line_items']['showQuantity'] ?? true,
                            'showLineTotal' => $blockData['line_items']['showLineTotal'] ?? true,
                        ])
                    @break

                    @case('totals')
                        @include('pdf.quotes.blocks.totals', [
                            'quote' => $quote,
                            'showSubtotal' => $blockData['totals']['showSubtotal'] ?? true,
                            'showGlobalDiscount' => $blockData['totals']['showGlobalDiscount'] ?? true,
                            'showTaxTotal' => $blockData['totals']['showTaxTotal'] ?? true,
                        ])
                    @break

                    @case('payment_terms')
                        @include('pdf.quotes.blocks.payment_terms', [
                            'labelText' => $blockData['payment_terms']['labelText'] ?? 'Payment Schedule',
                            'contextText' => $blockData['payment_terms']['contextText'] ?? '',
                        ])
                    @break

                    @case('terms')
                        @include('pdf.quotes.blocks.terms', [
                            'labelText' => $blockData['terms']['labelText'] ?? 'Terms & Conditions',
                            'contentHtml' => $blockData['terms']['contentHtml'] ?? '',
                        ])
                    @break

                    @case('signature')
                        @include('pdf.quotes.blocks.signature', [
                            'quote' => $quote,
                            'showContextText' => $blockData['signature']['showContextText'] ?? true,
                            'contextText' => $blockData['signature']['contextText'] ?? 'By signing, you agree to the terms and conditions above.',
                        ])
                    @break
                @endswitch
            @endif
        @endforeach
    </div>
</body>
</html>
