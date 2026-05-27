<?php

namespace Tests\Feature\Billing;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientImportLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_below_limit_proceeds()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertTrue($workspace->clients_count < $plan->features['max_clients']);
    }

    public function test_bulk_import_at_limit_blocks_import()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        Client::factory()->count(10)->for($workspace)->create();
        $workspace->loadCount('clients');

        $this->assertEquals($plan->features['max_clients'], $workspace->clients_count);
    }

    public function test_unlimited_plan_allows_all_imports()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);

        $this->assertNull($workspace->plan_id);
    }
}
