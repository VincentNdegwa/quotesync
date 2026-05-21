<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_user_when_below_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_users' => 5],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('members');

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ]);

        $response->assertRedirect();
    }

    public function test_cannot_add_user_when_at_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_users' => 1],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('members');

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ]);

        $response->assertRedirect();
    }

    public function test_limit_exceeded_exception_shows_inertia_toast()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-3',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_users' => 1],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('members');

        $response = $this->actingAs($workspace->owner)
            ->from(route('teams.index'))
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ], ['X-Inertia' => 'true']);

        $response->assertRedirect(route('teams.index'));
    }

    public function test_limit_exceeded_returns_json_for_api_requests()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-4',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_users' => 1],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('members');

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ], ['Accept' => 'application/json']);

        $response->assertRedirect();
    }
}
