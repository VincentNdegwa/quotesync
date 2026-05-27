<?php

namespace Tests\Unit\Services;

use App\Enums\Feature;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsageLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UsageLimitService::class);
    }

    public function test_can_perform_operation_when_limit_is_null()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => null]);

        $this->assertTrue($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_can_perform_operation_when_below_limit()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_users' => 5],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        // Create 3 members (owner + 2 more)
        $workspace->loadCount('members');

        $this->assertTrue($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_cannot_perform_operation_when_at_limit()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 3],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        Client::factory()->count(3)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertFalse($this->service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_cannot_perform_operation_when_above_limit()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-3',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 3],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertFalse($this->service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_get_current_usage_for_all_features()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        Client::factory()->count(3)->for($workspace)->create();
        CatalogItem::factory()->count(2)->for($workspace)->create();

        $workspace->loadCount(['clients', 'catalogItems', 'templates']);

        $this->assertEquals(3, $this->service->getCurrentUsage($workspace, Feature::MAX_CLIENTS));
        $this->assertEquals(2, $this->service->getCurrentUsage($workspace, Feature::MAX_CATALOG_ITEMS));
        $this->assertEquals(0, $this->service->getCurrentUsage($workspace, Feature::MAX_TEMPLATES));
    }

    public function test_get_usage_percentage_returns_null_for_unlimited()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => null]);

        $this->assertNull($this->service->getUsagePercentage($workspace, Feature::MAX_USERS));
    }

    public function test_get_usage_percentage_calculates_correctly()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-4',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 10],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertEquals(50.0, $this->service->getUsagePercentage($workspace, Feature::MAX_CLIENTS));
    }

    public function test_get_usage_percentage_caps_at_100()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-5',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 5],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        Client::factory()->count(10)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertEquals(100.0, $this->service->getUsagePercentage($workspace, Feature::MAX_CLIENTS));
    }

    public function test_get_limit_returns_null_for_unlimited()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => null]);

        $this->assertNull($this->service->getLimit($workspace, Feature::MAX_USERS));
    }

    public function test_get_limit_returns_integer()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-6',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => '10'],
        ]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);

        $this->assertEquals(10, $this->service->getLimit($workspace, Feature::MAX_CLIENTS));
    }
}
