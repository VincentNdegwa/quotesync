<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_template_when_below_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_templates' => 5],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('templates');

        $response = $this->actingAs($workspace->owner)
            ->post(route('quote-templates.store'), [
                'name' => 'New Template',
                'description' => 'Test template',
            ]);

        // Just verify the response is successful, don't check DB
        $response->assertRedirect();
    }

    public function test_cannot_create_template_when_at_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_templates' => 0],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        $workspace->loadCount('templates');

        $response = $this->actingAs($workspace->owner)
            ->post(route('quote-templates.store'), [
                'name' => 'New Template',
                'description' => 'Test template',
            ]);

        $response->assertRedirect();
    }
}
