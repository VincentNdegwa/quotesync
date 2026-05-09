@php
    use App\Support\Pdf\BlockStyle;

    $style = BlockStyle::content($config, $theme);
    $label = $config['labelText'] ?? null;
    $content = $config['contextText'] ?? $quote->cover_message ?? ($settings['default_cover_message'] ?? null);
@endphp

@if($content)
    <div class="block" style="{{ $style }}">
        @if(($config['showLabel'] ?? false) && $label)
            <div class="text-eyebrow" style="margin-bottom: 8px; color: {{ $branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B' }};">
                {{ $label }}
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 12px;">
            {!! nl2br(e($content)) !!}
        </div>
    </div>
@endif
