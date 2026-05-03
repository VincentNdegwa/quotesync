<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note {{ $creditNote->credit_note_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        
        .company-info h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #111827;
        }
        
        .company-info p {
            margin: 4px 0;
            color: #6b7280;
        }
        
        .credit-note-number {
            text-align: right;
        }
        
        .credit-note-number h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: #111827;
        }
        
        .credit-note-number p {
            margin: 4px 0;
            color: #6b7280;
        }
        
        .title {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            color: #dc2626;
        }
        
        .subtitle {
            font-size: 16px;
            text-align: center;
            color: #6b7280;
            margin-bottom: 40px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .info-section h3 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin: 0 0 12px 0;
        }
        
        .info-section p {
            margin: 4px 0;
            color: #374151;
        }
        
        .reason-section {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 16px;
            margin-bottom: 40px;
        }
        
        .reason-section h3 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #dc2626;
            margin: 0 0 8px 0;
        }
        
        .reason-section p {
            margin: 0;
            color: #7f1d1d;
        }
        
        .line-items {
            margin-bottom: 40px;
        }
        
        .line-items table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .line-items th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            text-align: left;
            padding: 12px 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .line-items th.text-right {
            text-align: right;
        }
        
        .line-items td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        
        .line-items td.text-right {
            text-align: right;
        }
        
        .line-items td.description {
            color: #6b7280;
            font-size: 11px;
        }
        
        .totals {
            display: flex;
            justify-content: flex-end;
        }
        
        .totals-table {
            width: 300px;
        }
        
        .totals-table tr td {
            padding: 8px 0;
            text-align: right;
        }
        
        .totals-table .label {
            color: #6b7280;
            padding-right: 16px;
        }
        
        .totals-table .value {
            font-weight: 600;
            color: #111827;
        }
        
        .totals-table .total .label {
            font-weight: 600;
            color: #111827;
        }
        
        .totals-table .total .value {
            font-size: 16px;
            color: #dc2626;
        }
        
        .footer {
            margin-top: 60px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-draft {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .status-issued {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-applied {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-voided {
            background: #fee2e2;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <h1>{{ $creditNote->workspace->name ?? 'Company Name' }}</h1>
                @if($creditNote->workspace->owner)
                    <p>{{ $creditNote->workspace->owner->name }}</p>
                @endif
                <p>{{ $creditNote->workspace->email ?? '' }}</p>
                @if($creditNote->workspace->phone)
                    <p>{{ $creditNote->workspace->phone }}</p>
                @endif
            </div>
            <div class="credit-note-number">
                <span class="status-badge status-{{ $creditNote->status }}">{{ $creditNote->status }}</span>
                <h2>Credit Note</h2>
                <p><strong>{{ $creditNote->credit_note_number }}</strong></p>
                <p>Issue Date: {{ \Carbon\Carbon::parse($creditNote->issue_date)->format('F j, Y') }}</p>
                @if($creditNote->due_date)
                    <p>Due Date: {{ \Carbon\Carbon::parse($creditNote->due_date)->format('F j, Y') }}</p>
                @endif
            </div>
        </div>

        <h1 class="title">CREDIT NOTE</h1>
        <p class="subtitle">{{ $creditNote->title }}</p>

        <div class="info-grid">
            <div class="info-section">
                <h3>Billed To</h3>
                <p><strong>{{ $creditNote->client->company_name }}</strong></p>
                @if($creditNote->client->email)
                    <p>{{ $creditNote->client->email }}</p>
                @endif
                @if($creditNote->client->phone)
                    <p>{{ $creditNote->client->phone }}</p>
                @endif
                @if($creditNote->client->address)
                    <p>{{ $creditNote->client->address }}</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Related Invoice</h3>
                @if($creditNote->invoice)
                    <p><strong>{{ $creditNote->invoice->invoice_number }}</strong></p>
                    <p>{{ $creditNote->invoice->title }}</p>
                @else
                    <p>No related invoice</p>
                @endif
            </div>
        </div>

        <div class="reason-section">
            <h3>Reason for Credit Note</h3>
            <p>{{ $creditNote->reason }}</p>
        </div>

        <div class="line-items">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNote->lineItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->name }}</strong>
                                @if($item->description)
                                    <div class="description">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="text-right">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                            <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ $creditNote->currency }} {{ number_format($creditNote->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">{{ $creditNote->currency }} {{ number_format($creditNote->tax_amount, 2) }}</td>
                </tr>
                <tr class="total">
                    <td class="label">Total Credit</td>
                    <td class="value">{{ $creditNote->currency }} {{ number_format($creditNote->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>This credit note was generated on {{ \Carbon\Carbon::parse($creditNote->created_at)->format('F j, Y') }}.</p>
        </div>
    </div>
</body>
</html>
