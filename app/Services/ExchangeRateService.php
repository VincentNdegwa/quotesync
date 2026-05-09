<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const EXCHANGE_RATE_API = 'https://api.exchangerate-api.com/v4/latest';

    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "fx_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($from, $to) {
            try {
                $response = Http::timeout(5)->get(self::EXCHANGE_RATE_API.'/'.$from);

                if ($response->successful() && $response->json("rates.{$to}")) {
                    return (float) $response->json("rates.{$to}");
                }
            } catch (\Exception $e) {
                Log::error('ExchangeRate API Error: '.$e->getMessage());
            }

            return 1.0;
        });
    }

    public function convert(float $amount, string $from, string $to): array
    {
        $rate = $this->getRate($from, $to);

        return [
            'rate' => $rate,
            'amount' => round($amount * $rate, 2),
        ];
    }
}
