<div class="block signature">
    <div class="signature-label">Signature</div>
    
    @if($quote->signature_path)
        <img src="{{ $quote->signature_path }}" class="signature-image" alt="Signature">
    @endif
    
    @if($showContextText)
        <div class="signature-info">{{ $contextText }}</div>
    @endif
    
    @if($quote->accepted_at)
        <div class="signature-info">Signed on: {{ $quote->accepted_at->format('M d, Y H:i') }}</div>
    @endif
</div>
