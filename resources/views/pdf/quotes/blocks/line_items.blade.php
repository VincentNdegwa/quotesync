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
            @foreach($quote->sections as $section)
                @if($showSectionTitles)
                    <tr>
                        <td colspan="4" style="background-color: #f3f4f6; font-weight: bold; padding: 8px 12px;">
                            {{ $section->title }}
                        </td>
                    </tr>
                @endif

                @foreach($section->lineItems as $item)
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
            @endforeach
        </tbody>
    </table>
</div>
