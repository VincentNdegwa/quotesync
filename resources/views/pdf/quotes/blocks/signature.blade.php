@php
    use App\Support\Pdf\BlockStyle;

    $style = BlockStyle::base($config, $theme);
    $contextText = $config['contextText'] ?? 'By signing, you agree to the terms and conditions above.';
    $showContextText = $config['showContextText'] ?? true;
    $showTimestamp = $config['showTimestamp'] ?? true;
    $showIpAddress = $config['showIpAddress'] ?? false;
    $showAcceptedBanner = $config['showAcceptedBanner'] ?? true;
    $showDeclinedBanner = $config['showDeclinedBanner'] ?? true;
    $isDeclined = $quote->status?->value === 'declined';
    $isAccepted = $quote->accepted_at !== null;
@endphp

<div class="block" style="{{ $style }}">
    <div class="signature-panel">
        <div style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: {{ $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB' }};">
            {{ __('Signature') }}
        </div>

        @if($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="Signature">
        @else
            <div class="text-muted" style="margin: 24px 0 12px; font-style: italic;">
                {{ __('No signature captured yet.') }}
            </div>
        @endif

        @if($showContextText && $contextText)
            <div class="text-muted" style="margin-top: 12px; font-size: 12px;">
                {{ $contextText }}
            </div>
        @endif

        <div style="margin-top: 16px; font-size: 12px; color: #4B5563;">
            @if($showTimestamp)
                <div>
                    <strong>{{ __('Signed on') }}:</strong>
                    {{ $quote->accepted_at ? $quote->accepted_at->timezone($quote->workspace?->timezone ?? config('app.timezone'))->format('M d, Y H:i') : __('Pending') }}
                </div>
            @endif

            @if($showIpAddress)
                <div>
                    <strong>{{ __('IP address') }}:</strong>
                    {{ $quote->signer_ip ?? __('Unavailable') }}
                </div>
            @endif

            @if($quote->signer_name)
                <div>
                    <strong>{{ __('Signed by') }}:</strong>
                    {{ $quote->signer_name }}
                </div>
            @endif
        </div>

        @if($showAcceptedBanner && $isAccepted)
            <div style="margin-top: 18px; display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background-color: {{ ($branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB').'15' }}; color: {{ $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB' }}; font-size: 12px; font-weight: 600;">
                {{ __('Quote accepted') }}
            </div>
        @elseif($showDeclinedBanner && $isDeclined)
            <div style="margin-top: 18px; display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background-color: #FEE2E2; color: #B91C1C; font-size: 12px; font-weight: 600;">
                {{ __('Quote declined') }}
            </div>
        @endif
    </div>
</div>
