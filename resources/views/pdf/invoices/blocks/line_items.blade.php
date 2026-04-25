<div class="block">
    <table class="line-items-table">
        <thead>
            <tr>
                <th>Description</th>
                @if($showQuantity)
                    <th>Qty</th>
                @endif
                @if($showUnitPrice)
                    <th>Price</th>
                @endif
                @if($showLineTotal)
                    <th>Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lineItems as $item)
                <tr>
                    <td>
                        <div style="font-weight: bold;">{{ $item->name }}</div>
                        @if($showItemDescription && $item->description)
                            <div style="font-size: 12px; color: #6b7280;">{{ $item->description }}</div>
                        @endif
                    </td>
                    @if($showQuantity)
                        <td>{{ $item->quantity }}</td>
                    @endif
                    @if($showUnitPrice)
                        <td>\${{ $item->unit_price }}</td>
                    @endif
                    @if($showLineTotal)
                        <td>\${{ $item->total }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
