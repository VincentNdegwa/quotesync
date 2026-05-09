@php
    $primaryColor = $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB';
@endphp

<div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
    <div style="display: flex; align-items: baseline; gap: 12px;">
        <span style="font-weight: 600; color: {{ $primaryColor }};">{{ $branding['company_name'] ?? __('Quote') }}</span>
        @if($config['showQuoteNumber'] ?? true)
            <span class="text-muted" style="font-size: 13px;">#{{ $quote->number ?? 'Draft' }}</span>
        @endif
    </div>

    <div style="font-size: 12px; color: #4B5563; display: flex; gap: 12px;">
        @if($config['showIssueDate'] ?? true)
            <span>{{ __('Issued:') }} {{ $issueDate ?? '-' }}</span>
        @endif
        @if($config['showValidUntil'] ?? true)
            <span>{{ __('Valid until:') }} {{ $validUntil ?? '-' }}</span>
        @endif
    </div>
</div>
