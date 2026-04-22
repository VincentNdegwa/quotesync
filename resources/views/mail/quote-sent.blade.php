<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;background:#f9fafb;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $companyName }} logo" style="height:32px;max-width:140px;object-fit:contain;display:block;">
                                    @endif
                                </td>
                                <td align="right" style="font-size:18px;font-weight:700;color:#111827;">{{ $companyName }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#111827;">Your quote is ready</h1>
                        @if ($messageBody !== '')
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#374151;white-space:pre-line;">{{ $messageBody }}</p>
                        @endif

                        @if ($coverMessage)
                            <div style="margin:0 0 16px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;font-size:13px;line-height:1.6;color:#374151;white-space:pre-line;">{{ $coverMessage }}</div>
                        @endif

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">Quote</td>
                                <td style="padding:10px 12px;background:#f9fafb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right;">Total</td>
                            </tr>
                            <tr>
                                <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:600;color:#111827;">
                                    {{ $quoteNumber }} - {{ $quoteTitle }}
                                </td>
                                <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;text-align:right;">
                                    {{ $quoteTotal }}
                                </td>
                            </tr>
                        </table>

                        @if (!empty($lineItems))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">Item</td>
                                    <td style="padding:10px 12px;background:#f9fafb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right;">Qty</td>
                                    <td style="padding:10px 12px;background:#f9fafb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right;">Total</td>
                                </tr>
                                @foreach ($lineItems as $lineItem)
                                    <tr>
                                        <td style="padding:10px 12px;border-top:1px solid #e5e7eb;font-size:13px;color:#111827;">{{ $lineItem['name'] }}</td>
                                        <td style="padding:10px 12px;border-top:1px solid #e5e7eb;font-size:13px;color:#374151;text-align:right;">{{ $lineItem['quantity'] }}</td>
                                        <td style="padding:10px 12px;border-top:1px solid #e5e7eb;font-size:13px;color:#111827;text-align:right;">{{ $lineItem['total'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        <p style="margin:0 0 20px;text-align:center;">
                            <a href="{{ $viewUrl }}" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#111827;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                                View &amp; Accept Quote
                            </a>
                        </p>

                        @if ($validUntil)
                            <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;">Valid until {{ $validUntil }}</p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:14px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;font-size:12px;color:#6b7280;">
                        <p style="margin:0 0 6px;">You are receiving this email because a quote was shared with you.</p>
                        @if ($unsubscribeUrl)
                            <p style="margin:0;"><a href="{{ $unsubscribeUrl }}" style="color:#6b7280;text-decoration:underline;">Unsubscribe / opt-out</a></p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
