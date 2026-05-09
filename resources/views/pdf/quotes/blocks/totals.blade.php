@php
    use App\Support\Pdf\BlockStyle;
    use Illuminate\Support\Number;

    $style = BlockStyle::base($config, $theme);
    $currency = $quote->currency ?? 'USD';
    $formatMoney = fn ($value) => Number::currency((float) $value, $currency);
    $showSubtotal = $config['showSubtotal'] ?? true;
    $showGlobalDiscount = $config['showGlobalDiscount'] ?? true;
    $showTaxBreakdown = $config['showTaxBreakdown'] ?? false;
    $showTaxTotal = $config['showTaxTotal'] ?? false;
    $highlightTotal = $config['highlightTotal'] ?? true;
    $totalLabel = $config['totalLabel'] ?? __('Total');
    $alignment = $config['alignment'] ?? 'right';
    $styleVariant = $config['style'] ?? 'default';
    $background = $styleVariant === 'card' ? (($config['totalRowBackground'] ?? null) ?: '#F9FAFB') : null;

    $activeLineItems = $quote->sections
        ->flatMap(fn ($section) => $section->lineItems)
        ->filter(fn ($item) => ! $item->is_optional);

    $lineItemSubtotal = fn ($item) => (
        max((float) $item->quantity, 0)
        * max((float) $item->unit_price, 0)
        * (1 - min(max((float) $item->discount_percent, 0), 100) / 100)
    );

    $computedSubtotal = $activeLineItems->sum(fn ($item) => $lineItemSubtotal($item));

    $taxBreakdown = collect();

    if ($showTaxBreakdown) {
        $taxBreakdown = $activeLineItems->reduce(function ($carry, $item) use ($lineItemSubtotal) {
            $subtotal = $lineItemSubtotal($item);

            if ($subtotal <= 0) {
                return $carry;
            }

            $itemTaxes = $item->taxes ?? collect();

            if ($itemTaxes->isEmpty()) {
                return $carry;
            }

            $itemTaxes->each(function ($tax) use (&$carry, $subtotal) {
                $key = $tax->tax_label . '|' . $tax->tax_rate;
                $amount = $subtotal * ((float) $tax->tax_rate / 100);

                $carry[$key] = [
                    'label' => $tax->tax_label . ' (' . ($tax->tax_rate + 0) . '%)',
                    'amount' => ($carry[$key]['amount'] ?? 0) + $amount,
                ];
            });

            return $carry;
        }, collect());
    }

    $computedTaxAmount = $taxBreakdown->isNotEmpty()
        ? $taxBreakdown->sum('amount')
        : $activeLineItems->sum(fn ($item) => (float) $item->tax_amount);

    if ($computedTaxAmount <= 0 && $taxBreakdown->isNotEmpty()) {
        $computedTaxAmount = $taxBreakdown->sum('amount');
    }

    $computedDiscountAmount = max((float) $quote->discount_amount, 0);
    $computedTotal = $computedSubtotal + $computedTaxAmount - $computedDiscountAmount;
@endphp

<div class="block" style="{{ $style }}">
    <div class="totals" style="align-items: {{ $alignment === 'full-width' ? 'stretch' : 'flex-end' }}; text-align: {{ $alignment === 'center' ? 'center' : 'right' }};">
        @if($showSubtotal)
            <div class="totals-row">
                <span class="totals-label">{{ __('Subtotal') }}</span>
                <span class="totals-value">{{ $formatMoney($computedSubtotal ?: $quote->subtotal) }}</span>
            </div>
        @endif

        @if($showGlobalDiscount && $computedDiscountAmount > 0)
            <div class="totals-row">
                <span class="totals-label">{{ __('Discount') }}</span>
                <span class="totals-value">-{{ $formatMoney($computedDiscountAmount) }}</span>
            </div>
        @endif

        @if($showTaxBreakdown && $taxBreakdown->isNotEmpty())
            @foreach($taxBreakdown as $entry)
                <div class="totals-row" style="font-size: 13px; color: #4B5563;">
                    <span>{{ $entry['label'] }}</span>
                    <span>{{ $formatMoney($entry['amount']) }}</span>
                </div>
            @endforeach
        @endif

        @if($showTaxTotal && $computedTaxAmount > 0)
            <div class="totals-row">
                <span class="totals-label">{{ __('Tax') }}</span>
                <span class="totals-value">{{ $formatMoney($computedTaxAmount) }}</span>
            </div>
        @endif

        @if($quote->requires_deposit)
            <div class="totals-row" style="font-size: 13px;">
                <span class="totals-label">{{ __('Deposit Required') }}</span>
                <span class="totals-value">{{ $formatMoney($quote->deposit_amount) }}</span>
            </div>
        @endif

        <div class="totals-row" style="{{ $background ? 'background-color: '.$background.'; padding: 14px 16px; border-radius: 12px;' : '' }}">
            <span class="totals-label" style="font-weight: 600; color: {{ $highlightTotal ? ($branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB') : '#111827' }};">
                {{ $totalLabel }}
            </span>
            <span class="totals-value" style="font-size: {{ $highlightTotal ? '22px' : '18px' }}; color: {{ $highlightTotal ? ($branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB') : '#111827' }};">
                {{ $formatMoney($computedTotal ?: $quote->total) }}
            </span>
        </div>
    </div>
</div>
