<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\QuoteShortCode;
use Illuminate\Support\Str;

class QuoteShortCodeService
{
    public function resolveQuoteByIdentifier(string $identifier): ?Quote
    {
        $quote = Quote::query()
            ->where('quote_uuid', $identifier)
            ->first();

        if ($quote instanceof Quote) {
            return $quote;
        }

        if (strlen($identifier) !== 6) {
            return null;
        }

        return Quote::query()
            ->whereHas('shortCode', fn ($query) => $query->where('code', strtoupper($identifier)))
            ->first();
    }

    public function getOrCreateCode(Quote $quote): string
    {
        $existingCode = $quote->shortCode?->code;

        if (is_string($existingCode) && $existingCode !== '') {
            return $existingCode;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = strtoupper(Str::random(6));

            if (QuoteShortCode::query()->where('code', $code)->exists()) {
                continue;
            }

            $record = QuoteShortCode::query()->firstOrCreate(
                ['quote_id' => $quote->id],
                ['code' => $code],
            );

            return $record->code;
        }

        throw new \RuntimeException('Unable to generate a unique short code for quote.');
    }
}
