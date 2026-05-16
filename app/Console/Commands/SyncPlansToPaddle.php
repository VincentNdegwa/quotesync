<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:sync-plans-to-paddle')]
#[Description('Sync plans from database to Paddle')]
class SyncPlansToPaddle extends Command
{
    public function handle()
    {
        $apiKey = config('cashier.api_key');
        $baseUrl = config('cashier.sandbox') 
            ? 'https://sandbox-api.paddle.com' 
            : 'https://api.paddle.com';

        $this->info('Syncing plans to Paddle...');

        $plans = Plan::where('is_active', true)->ordered()->get();

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->name}");

            try {
                $productResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->post($baseUrl.'/products', [
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'tax_category' => 'standard',
                ]);

                if ($productResponse->failed()) {
                    $this->error("Failed to create product for {$plan->name}: {$productResponse->body()}");
                    continue;
                }

                $product = $productResponse->json()['data'];
                $productId = $product['id'];

                $this->info("Created product: {$productId}");

                $monthlyPriceResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->post($baseUrl.'/prices', [
                    'product_id' => $productId,
                    'unit_price' => [
                        'amount' => (string)($plan->monthly_price * 100),
                        'currency_code' => 'USD',
                    ],
                    'description' => $plan->name.' - Monthly',
                    'billing_cycle' => [
                        'interval' => 'month',
                        'frequency' => 1,
                    ],
                ]);

                if ($monthlyPriceResponse->successful()) {
                    $monthlyPriceId = $monthlyPriceResponse->json()['data']['id'];
                    $plan->paddle_monthly_price_id = $monthlyPriceId;
                    $plan->save();
                    $this->info("Monthly price ID: {$monthlyPriceId}");
                } else {
                    $this->error("Failed to create monthly price: ".$monthlyPriceResponse->body());
                }

                $yearlyPriceResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->post($baseUrl.'/prices', [
                    'product_id' => $productId,
                    'unit_price' => [
                        'amount' => (string)($plan->yearly_price * 100),
                        'currency_code' => 'USD',
                    ],
                    'description' => $plan->name.' - Yearly',
                    'billing_cycle' => [
                        'interval' => 'year',
                        'frequency' => 1,
                    ],
                ]);

                if ($yearlyPriceResponse->successful()) {
                    $yearlyPriceId = $yearlyPriceResponse->json()['data']['id'];
                    $plan->paddle_yearly_price_id = $yearlyPriceId;
                    $plan->save();
                    $this->info("Yearly price ID: {$yearlyPriceId}");
                } else {
                    $this->error("Failed to create yearly price: ".$yearlyPriceResponse->body());
                }

                $this->info("✓ Successfully synced {$plan->name}");
            } catch (\Exception $e) {
                $this->error("Error syncing {$plan->name}: {$e->getMessage()}");
            }
        }

        $this->info('Plan sync completed!');
    }
}
