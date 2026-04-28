@php
    use App\Support\Pdf\BlockStyle;
    use Illuminate\Support\Number;

    $style = BlockStyle::base($config, $theme);
    $fontStyle = BlockStyle::fontSize($config['fontSize'] ?? null);
    $tableStyle = $config['tableStyle'] ?? 'default';
    $currency = $quote->currency ?? 'USD';
    $showSectionTitles = $config['showSectionTitles'] ?? true;
    $showSectionSubtotals = $config['showSectionSubtotals'] ?? false;
    $showItemDescription = $config['showItemDescription'] ?? true;
    $showSku = $config['showSku'] ?? false;
    $showQuantity = $config['showQuantity'] ?? true;
    $showUnit = $config['showUnit'] ?? true;
    $showUnitPrice = $config['showUnitPrice'] ?? true;
    $showDiscount = $config['showDiscount'] ?? false;
    $showTax = $config['showTax'] ?? false;
    $showLineTotal = $config['showLineTotal'] ?? true;
    $optionalStyle = $config['optionalItemStyle'] ?? 'badge';
    $showOptionalBadge = $config['showOptionalBadge'] ?? false;
    $alternateRowColor = $config['alternateRowColor'] ?? false;
    $headerBackground = $config['headerBackground'] ?? null;
    $columnWidths = $config['columnWidths'] ?? [];

    $formatMoney = fn ($value) => Number::currency((float) $value, $currency);

    $columnCount = 1
        + ($showQuantity ? 1 : 0)
        + ($showUnitPrice ? 1 : 0)
        + ($showDiscount ? 1 : 0)
        + ($showTax ? 1 : 0)
        + ($showLineTotal ? 1 : 0);
@endphp

<div class="block" style="{{ $style }} {{ $fontStyle }}">
    @forelse($quote->sections as $section)
        <div style="margin-bottom: 24px;">
            @if($showSectionTitles && $section->title)
                <div class="section-title" style="margin-bottom: 12px;">
                    {{ $section->title }}
                </div>
            @elseif($showSectionTitles && ! $section->title)
                <div class="text-muted" style="font-style: italic; margin-bottom: 12px;">Untitled section</div>
            @endif

            @if(in_array($tableStyle, ['default', 'bordered', 'striped'], true))
                <table class="table {{ $tableStyle === 'striped' ? 'table-striped' : '' }} {{ $tableStyle === 'bordered' ? 'table-bordered' : '' }}" style="width: 100%;">
                    <thead>
                        <tr style="background-color: {{ $headerBackground ?? ($branding['primary_color'] ?? $theme['primaryColor'] ?? '#2563EB').'0D' }};">
                            <th style="text-align: left; padding: 10px 14px; width: {{ 100 - collect($columnWidths)->except(['description'])->sum() }}%;">Item</th>
                            @if($showQuantity)
                                <th style="text-align: right; padding: 10px 14px; width: {{ $columnWidths['quantity'] ?? 12 }}%;">Qty</th>
                            @endif
                            @if($showUnitPrice)
                                <th style="text-align: right; padding: 10px 14px; width: {{ $columnWidths['unitPrice'] ?? 16 }}%;">Unit</th>
                            @endif
                            @if($showDiscount)
                                <th style="text-align: right; padding: 10px 14px; width: {{ $columnWidths['discount'] ?? 10 }}%;">Disc</th>
                            @endif
                            @if($showTax)
                                <th style="text-align: right; padding: 10px 14px; width: {{ $columnWidths['tax'] ?? 10 }}%;">Tax</th>
                            @endif
                            @if($showLineTotal)
                                <th style="text-align: right; padding: 10px 14px; width: {{ $columnWidths['total'] ?? 16 }}%;">Total</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section->lineItems as $index => $item)
                            @php
                                $isOptional = (bool) $item->is_optional;
                                $rowStyle = $alternateRowColor && $index % 2 === 1
                                    ? 'background-color: #F9FAFB;'
                                    : '';
                            @endphp
                            <tr style="{{ $rowStyle }}">
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 600;">{{ $item->name ?? 'Line item' }}</div>
                                    @if($showItemDescription && $item->description)
                                        <div class="text-muted" style="margin-top: 4px; font-size: 12px;">
                                            {{ $item->description }}
                                        </div>
                                    @endif
                                    @if($showSku && $item->catalogItem?->sku)
                                        <div class="text-muted" style="margin-top: 2px; font-size: 11px;">
                                            SKU {{ $item->catalogItem?->sku }}
                                        </div>
                                    @endif

                                    @if($isOptional && $showOptionalBadge && $optionalStyle === 'badge')
                                        <span class="badge" style="margin-top: 8px; background-color: {{ ($branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B').'15' }}; color: {{ $branding['accent_color'] ?? $theme['accentColor'] ?? '#F59E0B' }};">
                                            Optional
                                        </span>
                                    @elseif($isOptional && $optionalStyle === 'checkbox')
                                        <div style="margin-top: 8px; font-size: 12px;">
                                            <input type="checkbox" checked style="margin-right: 6px;" disabled>
                                            Optional
                                        </div>
                                    @endif
                                </td>
                                @if($showQuantity)
                                    <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                        {{ $item->quantity }}@if($showUnit && $item->unit) <span style="color: #9CA3AF;"> {{ $item->unit }}</span>@endif
                                    </td>
                                @endif
                                @if($showUnitPrice)
                                    <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                        {{ $formatMoney($item->unit_price) }}
                                    </td>
                                @endif
                                @if($showDiscount)
                                    <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                        {{ $item->discount_percent ? $item->discount_percent.'%' : '—' }}
                                    </td>
                                @endif
                                @if($showTax)
                                    <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                        {{ $item->tax_amount ? $formatMoney($item->tax_amount) : '—' }}
                                    </td>
                                @endif
                                @if($showLineTotal)
                                    <td style="padding: 12px 14px; text-align: right; font-weight: 600; white-space: nowrap;">
                                        {{ $formatMoney($item->total) }}
                                    </td>
                                @endif
                            </tr>
                            @if($isOptional && $optionalStyle === 'greyed')
                                <tr>
                                    <td colspan="{{ $columnCount }}" class="text-muted" style="padding: 6px 14px 16px; font-size: 12px;">
                                        Optional item
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    @foreach($section->lineItems as $item)
                        <div style="padding: 18px 20px; border: 1px solid #E5E7EB; border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; gap: 16px;">
                                <div>
                                    <div style="font-weight: 600;">{{ $item->name ?? 'Line item' }}</div>
                                    @if($showItemDescription && $item->description)
                                        <div class="text-muted" style="margin-top: 6px; font-size: 12px;">
                                            {{ $item->description }}
                                        </div>
                                    @endif
                                    <div class="text-muted" style="margin-top: 6px; font-size: 11px; display: flex; gap: 12px; flex-wrap: wrap;">
                                        @if($showQuantity)
                                            <span>{{ $item->quantity }}@if($showUnit && $item->unit) {{ $item->unit }}@endif</span>
                                        @endif
                                        @if($showUnitPrice)
                                            <span>{{ $formatMoney($item->unit_price) }}</span>
                                        @endif
                                        @if($showDiscount && $item->discount_percent)
                                            <span>{{ $item->discount_percent }}% disc</span>
                                        @endif
                                        @if($showTax && $item->tax_amount)
                                            <span>Tax {{ $formatMoney($item->tax_amount) }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if($showLineTotal)
                                    <div style="font-weight: 600; white-space: nowrap;">{{ $formatMoney($item->total) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($showSectionSubtotals)
                @php($subtotal = $section->lineItems->sum(fn ($row) => (float) $row->total))
                <div style="margin-top: 12px; text-align: right; font-size: 13px;">
                    <span class="text-muted">Section subtotal</span>
                    <span style="margin-left: 12px; font-weight: 600;">{{ $formatMoney($subtotal) }}</span>
                </div>
            @endif
        </div>
    @empty
        <div class="text-muted" style="font-style: italic;">No line items have been added.</div>
    @endforelse
</div>
