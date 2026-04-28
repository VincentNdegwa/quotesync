@php
    $primaryColor = $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB';
    $accentColor = $branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B';
@endphp

<div style="display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center;">
    @if($logoSource && ($config['showLogo'] ?? true))
        <img src="{{ $logoSource }}" alt="Company logo" style="height: 64px; width: auto; object-fit: contain;">
    @elseif($branding['company_name'] ?? null)
        <span style="font-size: 22px; font-weight: 700; color: {{ $primaryColor }};">
            {{ $branding['company_name'] }}
        </span>
    @endif

    <div>
        <div style="font-size: 26px; font-weight: 700; color: {{ $branding['primary_color'] ?? '#111827' }};">
            {{ $quote->title ?? $quote->number ?? 'Quote' }}
        </div>
        @if(($branding['company_tagline'] ?? null) !== null)
            <div class="text-muted" style="margin-top: 4px; font-size: 13px;">
                {{ $branding['company_tagline'] }}
            </div>
        @endif
    </div>

    <div style="display: flex; gap: 16px; font-size: 13px; color: #4B5563;">
        @if($config['showQuoteNumber'] ?? true)
            <span><strong>{{ __('Quote #:') }}</strong> {{ $quote->number ?? 'Draft' }}</span>
        @endif
        @if($config['showIssueDate'] ?? true)
            <span><strong>{{ __('Issued:') }}</strong> {{ $issueDate ?? '-' }}</span>
        @endif
        @if($config['showValidUntil'] ?? true)
            <span><strong>{{ __('Valid until:') }}</strong> {{ $validUntil ?? '-' }}</span>
        @endif
    </div>

    @if(($branding['company_address'] ?? null))
        <div class="text-muted" style="font-size: 12px; max-width: 460px;">
            {{ $branding['company_address'] }}
        </div>
    @endif

    @if(($config['showExpiryCountdown'] ?? false) && is_int($daysLeft) && $daysLeft >= 0)
        <span class="badge" style="background-color: {{ $daysLeft <= 7 ? '#FEF3C7' : $accentColor.'1A' }}; color: {{ $daysLeft <= 7 ? '#92400E' : $primaryColor }};">
            {{ __('Expires in :days days', ['days' => $daysLeft]) }}
        </span>
    @endif
</div>
