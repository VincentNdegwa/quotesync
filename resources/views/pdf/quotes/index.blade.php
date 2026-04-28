<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('pdf.quotes.styles', ['theme' => $theme])
    </style>
</head>
<body>
    <div class="quote-document">
        @foreach($blocks as $block)
            @continue(!($block['visible'] ?? true))

            @php($view = 'pdf.quotes.blocks.' . $block['type'])

            @if(\Illuminate\Support\Facades\View::exists($view))
                @include($view, [
                    'quote' => $quote,
                    'block' => $block,
                    'config' => $block['config'] ?? [],
                    'theme' => $theme,
                    'branding' => $branding,
                    'signatureDataUri' => $signatureDataUri,
                ])
            @endif
        @endforeach
    </div>
</body>
</html>
