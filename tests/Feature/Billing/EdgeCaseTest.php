<?php

namespace Tests\Feature\Billing;

use App\Enums\Feature;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlimited_plan_allows_unlimited_operations()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);

        Client::factory()->count(1000)->for($workspace)->create();
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);

        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
        $this->assertNull($service->getLimit($workspace, Feature::MAX_CLIENTS));
    }

    public function test_zero_limit_blocks_all_operations()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 0],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);

        $this->assertFalse($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_boolean_false_allows_unlimited()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => false],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        Client::factory()->count(100)->for($workspace)->create();
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);

        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_workspace_without_plan_uses_defaults()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);

        $service = app(UsageLimitService::class);

        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_usage_percentage_with_zero_limit_returns_100()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-3',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 0],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);

        $this->assertEquals(100.0, $service->getUsagePercentage($workspace, Feature::MAX_CLIENTS));
    }
}
