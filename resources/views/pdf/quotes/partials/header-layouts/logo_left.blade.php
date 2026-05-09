@php
    $primaryColor = $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB';
    $accentColor = $branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B';
@endphp

<div style="display: flex; justify-content: space-between; gap: 32px; align-items: flex-start;">
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @if($logoSource && ($config['showLogo'] ?? true))
            <img src="{{ $logoSource }}" alt="Company logo" style="height: 56px; width: auto; object-fit: contain;">
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
    </div>

    <div style="display: flex; flex-direction: column; gap: 6px; text-align: right; font-size: 13px; color: #4B5563;">
        @if($config['showQuoteNumber'] ?? true)
            <div style="font-weight: 600; color: #111827;">Quote #{{ $quote->number ?? 'Draft' }}</div>
        @endif
        @if($config['showIssueDate'] ?? true)
            <div>{{ __('Issued:') }} {{ $issueDate ?? '-' }}</div>
        @endif
        @if($config['showValidUntil'] ?? true)
            <div>{{ __('Valid until:') }} {{ $validUntil ?? '-' }}</div>
        @endif
        @if(($config['showExpiryCountdown'] ?? false) && is_int($daysLeft) && $daysLeft >= 0)
            <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                <span class="badge" style="background-color: {{ $daysLeft <= 7 ? '#FEF3C7' : $accentColor.'1A' }}; color: {{ $daysLeft <= 7 ? '#92400E' : $primaryColor }};">
                    {{ __('Expires in :days days', ['days' => $daysLeft]) }}
                </span>
            </div>
        @endif
    </div>
</div>
