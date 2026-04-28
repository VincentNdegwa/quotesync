@php
    use App\Support\Pdf\BlockStyle;

    $style = BlockStyle::base($config, $theme);
    $fontStyle = BlockStyle::fontSize($config['fontSize'] ?? null);
    $logoSource = $branding['logo_data_uri'] ?? $branding['logo_url'] ?? null;
    $layout = $config['layout'] ?? 'logo-left-details-right';
    $issueDate = $quote->created_at?->timezone($quote->workspace?->timezone ?? config('app.timezone'))?->format('M d, Y');
    $validUntil = $quote->valid_until?->format('M d, Y');
    $daysLeft = $quote->valid_until
        ? now()->startOfDay()->diffInDays($quote->valid_until->copy()->startOfDay(), false)
        : null;
@endphp

<div class="block" style="{{ $style }} {{ $fontStyle }}">
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @if($layout === 'logo-left-details-right')
            @include('pdf.quotes.partials.header-layouts.logo_left', compact('config', 'branding', 'quote', 'logoSource', 'issueDate', 'validUntil', 'daysLeft', 'theme'))
        @elseif($layout === 'logo-right-details-left')
            @include('pdf.quotes.partials.header-layouts.logo_right', compact('config', 'branding', 'quote', 'logoSource', 'issueDate', 'validUntil', 'daysLeft', 'theme'))
        @elseif($layout === 'centered')
            @include('pdf.quotes.partials.header-layouts.centered', compact('config', 'branding', 'quote', 'logoSource', 'issueDate', 'validUntil', 'daysLeft', 'theme'))
        @else
            @include('pdf.quotes.partials.header-layouts.minimal', compact('config', 'branding', 'quote', 'issueDate', 'validUntil', 'theme', 'daysLeft'))
        @endif
    </div>
</div>
