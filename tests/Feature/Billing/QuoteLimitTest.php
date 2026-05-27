<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Workspace;
use App\Models\WorkspaceUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_usage_resets_at_start_of_month()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_quotes_per_month' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);

        // Set usage for previous month
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s'),
            'quotes_sent' => 15,
        ]);

        // Create current month usage
        $currentUsage = WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'quotes_sent' => 0,
        ]);

        $this->assertEquals(0, $workspace->currentUsage()->quotes_sent);
    }
}
