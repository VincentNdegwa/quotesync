@php
    use App\Support\Pdf\BlockStyle;

    $style = BlockStyle::base($config, $theme);
    $fontStyle = BlockStyle::fontSize($config['fontSize'] ?? null);
    $layout = $config['layout'] ?? 'split';
    $companyLines = collect([
        $branding['company_name'] ?? null,
        $branding['company_address'] ?? null,
        ($config['showCompanyEmail'] ?? true) ? ($branding['company_email'] ?? null) : null,
        ($config['showCompanyPhone'] ?? true) ? ($branding['company_phone'] ?? null) : null,
    ])->filter();

    $client = $quote->client;
    $clientLines = collect([
        $client?->company_name,
        ($config['showClientEmail'] ?? true) ? $client?->email : null,
        ($config['showClientPhone'] ?? false) ? $client?->phone : null,
    ])->filter();
@endphp

<div class="block" style="{{ $style }} {{ $fontStyle }}">
    @if($layout === 'split')
        <div class="flex-row" style="gap: 48px;">
            <div class="flex-col">
                @if($config['showLabels'] ?? true)
                    <div class="text-eyebrow" style="color: {{ $theme['primaryColor'] ?? '#2563EB' }};">From</div>
                @endif
                @foreach($companyLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>

            <div class="flex-col" style="text-align: right;">
                @if($config['showLabels'] ?? true)
                    <div class="text-eyebrow" style="color: {{ $theme['primaryColor'] ?? '#2563EB' }};">To</div>
                @endif
                @forelse($clientLines as $line)
                    <div>{{ $line }}</div>
                @empty
                    <div class="text-muted">Client details pending</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="flex-col" style="gap: 18px;">
            <div>
                @if($config['showLabels'] ?? true)
                    <div class="text-eyebrow" style="color: {{ $theme['primaryColor'] ?? '#2563EB' }};">From</div>
                @endif
                @foreach($companyLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>

            <div>
                @if($config['showLabels'] ?? true)
                    <div class="text-eyebrow" style="color: {{ $theme['primaryColor'] ?? '#2563EB' }};">To</div>
                @endif
                @forelse($clientLines as $line)
                    <div>{{ $line }}</div>
                @empty
                    <div class="text-muted">Client details pending</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
