<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Services\PaddleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-plans-to-paddle')]
#[Description('Sync plans from database to Paddle')]
class SyncPlansToPaddle extends Command
{
    public function __construct(
        private PaddleService $paddleService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Syncing plans to Paddle...');

        $plans = Plan::where('is_active', true)->ordered()->get();

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->name}");

            try {
                $success = $this->paddleService->syncPlan($plan);

                if ($success) {
                    $this->info("✓ Successfully synced {$plan->name}");
                    $this->info("Monthly price ID: {$plan->paddle_monthly_price_id}");
                    $this->info("Yearly price ID: {$plan->paddle_yearly_price_id}");
                } else {
                    $this->error("✗ Failed to sync {$plan->name}");
                }
            } catch (\Exception $e) {
                $this->error("Error syncing {$plan->name}: {$e->getMessage()}");
            }
        }

        $this->info('Plan sync completed!');
    }
}
