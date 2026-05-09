<div class="block totals">
    @if($showSubtotal)
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-value">\${{ $invoice->subtotal }}</span>
        </div>
    @endif
    
    @if($showGlobalDiscount && $invoice->discount_amount > 0)
        <div class="total-row">
            <span class="total-label">Discount:</span>
            <span class="total-value">-\${{ $invoice->discount_amount }}</span>
        </div>
    @endif
    
    @if($showTaxTotal && $invoice->tax_amount > 0)
        <div class="total-row">
            <span class="total-label">Tax:</span>
            <span class="total-value">\${{ $invoice->tax_amount }}</span>
        </div>
    @endif
    
    @if($showPaidAmount && $invoice->paid_amount > 0)
        <div class="total-row">
            <span class="total-label">Paid:</span>
            <span class="total-value">\${{ $invoice->paid_amount }}</span>
        </div>
    @endif
    
    @if($showBalanceDue)
        <div class="total-row total-final">
            <span class="total-label">Balance Due:</span>
            <span class="total-value">\${{ $invoice->balance_due }}</span>
        </div>
    @endif
</div>
