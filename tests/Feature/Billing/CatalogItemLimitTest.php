<?php

namespace Tests\Feature\Billing;

use App\Models\CatalogItem;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogItemLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_catalog_item_when_below_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_catalog_items' => 50],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        CatalogItem::factory()->count(20)->for($workspace)->create();
        $workspace->loadCount('catalogItems');

        $response = $this->actingAs($workspace->owner)
            ->post(route('catalog.store'), [
                'name' => 'New Product',
                'sku' => 'PROD-001',
                'unit_id' => 1,
                'unit_price' => 100,
            ]);

        $response->assertRedirect();
    }

    public function test_cannot_create_catalog_item_when_at_limit()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-2',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_catalog_items' => 50],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        CatalogItem::factory()->count(50)->for($workspace)->create();
        $workspace->loadCount('catalogItems');

        $response = $this->actingAs($workspace->owner)
            ->post(route('catalog.store'), [
                'name' => 'New Product',
                'sku' => 'PROD-001',
                'unit_id' => 1,
                'unit_price' => 100,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('catalog_items', ['name' => 'New Product']);
    }
}
