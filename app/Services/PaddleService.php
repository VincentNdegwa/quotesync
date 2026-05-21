<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Http;

class PaddleService
{
    protected ?string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('cashier.api_key');
        $this->baseUrl = config('cashier.sandbox')
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    public function syncPlan(Plan $plan): bool
    {
        $this->deleteExistingProducts($plan->name);
        $productId = $this->createProduct($plan);

        if (! $productId) {
            return false;
        }

        $monthlyPriceId = $this->createPrice($productId, $plan->monthly_price, 'month', $plan->name);
        $yearlyPriceId = $this->createPrice($productId, $plan->yearly_price, 'year', $plan->name);

        if ($monthlyPriceId) {
            $plan->paddle_monthly_price_id = $monthlyPriceId;
        }

        if ($yearlyPriceId) {
            $plan->paddle_yearly_price_id = $yearlyPriceId;
        }

        $plan->save();

        return true;
    }

    protected function deleteExistingProducts(string $planName): void
    {
        $response = $this->request('get', '/products');

        if ($response->failed()) {
            return;
        }

        $existingProducts = collect($response->json()['data'])
            ->filter(fn ($product) => $product['name'] === $planName);

        foreach ($existingProducts as $product) {
            $this->request('delete', "/products/{$product['id']}");
        }
    }

    protected function createProduct(Plan $plan): ?string
    {
        $response = $this->request('post', '/products', [
            'name' => $plan->name,
            'description' => $plan->description,
            'tax_category' => 'standard',
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json()['data']['id'];
    }

    protected function createPrice(string $productId, float $amount, string $interval, string $planName): ?string
    {
        $response = $this->request('post', '/prices', [
            'product_id' => $productId,
            'unit_price' => [
                'amount' => (string) ($amount * 100),
                'currency_code' => 'USD',
            ],
            'description' => "{$planName} - ".ucfirst($interval),
            'billing_cycle' => [
                'interval' => $interval,
                'frequency' => 1,
            ],
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json()['data']['id'];
    }

    protected function request(string $method, string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->{$method}($this->baseUrl.$endpoint, $data);
    }
}
