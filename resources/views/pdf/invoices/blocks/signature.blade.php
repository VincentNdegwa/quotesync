<div class="block signature">
    <div class="signature-label">Signature</div>
    
    @if($invoice->signature_path)
        <img src="{{ $invoice->signature_path }}" class="signature-image" alt="Signature">
    @endif
    
    @if($showContextText)
        <div class="signature-info">{{ $contextText }}</div>
    @endif
    
    @if($invoice->paid_at)
        <div class="signature-info">Paid on: {{ $invoice->paid_at->format('M d, Y H:i') }}</div>
    @endif
</div>
