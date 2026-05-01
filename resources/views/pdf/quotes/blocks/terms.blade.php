@php
    use App\Support\Pdf\BlockStyle;

    $style = BlockStyle::content($config, $theme);
    $label = $config['labelText'] ?? __('Terms & Conditions');
    $content = $config['contextText'] ?? $quote->terms ?? ($settings['default_terms'] ?? null);
@endphp

@if($content)
    <div class="block" style="{{ $style }}">
        @if($label)
            <div class="text-eyebrow" style="margin-bottom: 10px; color: {{ $branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B' }};">
                {{ $label }}
            </div>
        @endif

        <div style="font-size: 13px; line-height: 1.7;">
            {!! nl2br(e($content)) !!}
        </div>
    </div>
@endif
