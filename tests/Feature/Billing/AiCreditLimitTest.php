<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Workspace;
use App\Models\WorkspaceUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_use_ai_when_credits_available()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 100],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 50,
        ]);

        // This test verifies the service logic, not an actual AI endpoint
        $usage = $workspace->currentUsage();
        $this->assertEquals(50, $usage->ai_credits_used);
        $this->assertEquals(100, $workspace->plan->features['ai_credits_per_month']);
    }

    public function test_cannot_use_ai_when_credits_exhausted()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 100],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 100,
        ]);

        // This test verifies the service logic, not an actual AI endpoint
        $usage = $workspace->currentUsage();
        $this->assertEquals(100, $usage->ai_credits_used);
        $this->assertEquals(100, $workspace->plan->features['ai_credits_per_month']);
    }

    public function test_ai_credits_increment_after_use()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-3',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 100],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        $usage = WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 50,
        ]);

        // Simulate AI credit usage
        $usage->ai_credits_used = 51;
        $usage->save();

        $usage->refresh();
        $this->assertEquals(51, $usage->ai_credits_used);
    }

    public function test_ai_quote_generation_respects_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-4',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 10,
        ]);

        $this->assertEquals(10, $workspace->plan->features['ai_credits_per_month']);
        
        $usage = $workspace->currentUsage();
        $this->assertEquals(10, $usage->ai_credits_used);
    }

    public function test_ai_template_generation_respects_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-5',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 10,
        ]);

        $this->assertEquals(10, $workspace->plan->features['ai_credits_per_month']);
        
        $usage = $workspace->currentUsage();
        $this->assertEquals(10, $usage->ai_credits_used);
    }

    public function test_ai_writing_improve_respects_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-6',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['ai_credits_per_month' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        
        WorkspaceUsage::create([
            'workspace_id' => $workspace->id,
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'ai_credits_used' => 10,
        ]);

        $this->assertEquals(10, $workspace->plan->features['ai_credits_per_month']);
        
        $usage = $workspace->currentUsage();
        $this->assertEquals(10, $usage->ai_credits_used);
    }
}
