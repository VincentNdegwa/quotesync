@php
    use App\Support\Pdf\BlockStyle;
    use Illuminate\Support\Number;

    $style = BlockStyle::content($config, $theme);
    $label = $config['labelText'] ?? __('Payment Terms');
    $context = $config['contextText'] ?? ($settings['default_payment_terms'] ?? null);
    $showDepositInfo = $config['showDepositInfo'] ?? true;
    $showPaymentMethods = $config['showPaymentMethods'] ?? false;
    $paymentMethods = $config['paymentMethods'] ?? [];
    $hasContent = $context || ($showDepositInfo && $quote->requires_deposit) || ($showPaymentMethods && ! empty($paymentMethods));
@endphp

@if($hasContent)
    <div class="block" style="{{ $style }}">
        @if($label)
            <div class="text-eyebrow" style="margin-bottom: 10px; color: {{ $branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB' }};">
                {{ $label }}
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
            @if($context)
                <div>{!! nl2br(e($context)) !!}</div>
            @endif

            @if($showDepositInfo && $quote->requires_deposit)
                <div>
                    <strong>{{ __('Deposit Due') }}:</strong>
                    {{ Number::currency((float) $quote->deposit_amount, $quote->currency ?? 'USD') }}
                </div>
            @endif

            @if($showPaymentMethods && ! empty($paymentMethods))
                <div>
                    <strong>{{ __('Accepted Payment Methods') }}:</strong>
                    <span>{{ implode(', ', $paymentMethods) }}</span>
                </div>
            @endif
        </div>
    </div>
@endif
