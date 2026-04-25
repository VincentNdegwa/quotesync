<div class="block totals">
    @if($showSubtotal)
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-value">\${{ $quote->subtotal }}</span>
        </div>
    @endif
    
    @if($showGlobalDiscount && $quote->discount_amount > 0)
        <div class="total-row">
            <span class="total-label">Discount:</span>
            <span class="total-value">-\${{ $quote->discount_amount }}</span>
        </div>
    @endif
    
    @if($showTaxTotal && $quote->tax_amount > 0)
        <div class="total-row">
            <span class="total-label">Tax:</span>
            <span class="total-value">\${{ $quote->tax_amount }}</span>
        </div>
    @endif
    
    <div class="total-row total-final">
        <span class="total-label">Total:</span>
        <span class="total-value">\${{ $quote->total }}</span>
    </div>
</div>
