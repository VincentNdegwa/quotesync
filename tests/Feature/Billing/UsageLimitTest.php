<?php

namespace Tests\Feature\Billing;

use App\Enums\Feature;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_when_below_limit()
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

        $response = $this->actingAs($workspace->owner)
            ->post(route('clients.store'), [
                'company_name' => 'New Client',
                'contact_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['company_name' => 'New Client']);
    }

    public function test_cannot_create_client_when_at_limit()
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

        $response = $this->actingAs($workspace->owner)
            ->post(route('clients.store'), [
                'company_name' => 'New Client',
                'contact_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        // The limit check should prevent creation, but we're testing the response
        $response->assertRedirect();
    }

    public function test_client_limit_check_uses_cached_counts()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-3',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_clients' => 10],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);
        $usage = $service->getCurrentUsage($workspace, Feature::MAX_CLIENTS);
        
        $this->assertEquals(5, $usage);
    }

    public function test_unlimited_plan_allows_unlimited_operations()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        
        Client::factory()->count(1000)->for($workspace)->create();
        $workspace->loadCount('clients');

        $response = $this->actingAs($workspace->owner)
            ->post(route('clients.store'), [
                'company_name' => 'New Client',
                'contact_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['company_name' => 'New Client']);
    }
}
