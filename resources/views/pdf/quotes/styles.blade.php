@php
    $fontFamilies = [
        'inter' => "'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'manrope' => "'Manrope', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'lato' => "'Lato', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'work_sans' => "'Work Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'clash_display' => "'Clash Display', 'Helvetica Neue', Helvetica, Arial, sans-serif",
    ];

    $fontSizeMap = [
        'sm' => '13px',
        'md' => '15px',
        'lg' => '17px',
    ];

    $borderRadiusMap = [
        'none' => '0',
        'sm' => '4px',
        'md' => '8px',
        'lg' => '12px',
    ];

    $bodyFont = $fontFamilies[$theme['fontFamily'] ?? 'inter'] ?? $fontFamilies['inter'];
    $bodyFontSize = $fontSizeMap[$theme['fontSize'] ?? 'md'] ?? $fontSizeMap['md'];
    $baseRadius = $borderRadiusMap[$theme['borderRadius'] ?? 'md'] ?? $borderRadiusMap['md'];
@endphp

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    width: 100%;
    height: 100%;
}

body {
    font-family: {!! $bodyFont !!};
    font-size: {{ $bodyFontSize }};
    color: #111827;
    background-color: {{ $theme['backgroundColor'] ?? '#ffffff' }};
    line-height: 1.55;
}

.quote-document {
    max-width: 820px;
    margin: 0 auto;
    padding: 40px 48px 60px;
    background: #ffffff;
}

.block {
    margin-bottom: 32px;
}

.block:last-child {
    margin-bottom: 0;
}

.block strong {
    font-weight: 600;
}

.text-muted {
    color: #6B7280;
}

.text-eyebrow {
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.08em;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead th {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6B7280;
}

.table tbody td {
    font-size: 13px;
    vertical-align: top;
}

.table-striped tbody tr:nth-child(even) {
    background-color: #F9FAFB;
}

.table-bordered,
.table-bordered th,
.table-bordered td {
    border: 1px solid #E5E7EB;
}

.flex-row {
    display: flex;
    justify-content: space-between;
    gap: 40px;
}

.flex-col {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.section-title {
    font-weight: 600;
    color: {{ $theme['primaryColor'] ?? '#2563EB' }};
}

.totals {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.totals-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
}

.totals-label {
    color: #6B7280;
}

.totals-value {
    font-weight: 600;
    color: #111827;
}

.totals-highlight {
    color: {{ $theme['primaryColor'] ?? '#2563EB' }};
    font-size: 20px;
}

.signature-panel {
    border: 1px solid #E5E7EB;
    border-radius: {{ $baseRadius }};
    padding: 24px;
    text-align: center;
}

.signature-panel img {
    max-width: 220px;
    max-height: 120px;
    margin: 12px auto;
}

.spacing-none { margin-bottom: 0; }
.spacing-xs { margin-bottom: 12px; }
.spacing-sm { margin-bottom: 18px; }
.spacing-md { margin-bottom: 24px; }
.spacing-lg { margin-bottom: 32px; }
.spacing-xl { margin-bottom: 40px; }
