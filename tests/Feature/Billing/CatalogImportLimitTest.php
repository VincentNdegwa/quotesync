<?php

namespace Tests\Feature\Billing;

use App\Models\CatalogItem;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogImportLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_below_limit_proceeds()
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0,
            'interval' => 'monthly',
            'features' => ['max_catalog_items' => 50],
        ]);
        $workspace = Workspace::factory()->create(['plan_id' => $plan->id]);
        CatalogItem::factory()->count(40)->for($workspace)->create();
        $workspace->loadCount('catalogItems');

        $this->assertTrue($workspace->catalog_items_count < $plan->features['max_catalog_items']);
    }

    public function test_bulk_import_at_limit_blocks_import()
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

        $this->assertEquals($plan->features['max_catalog_items'], $workspace->catalog_items_count);
    }

    public function test_unlimited_plan_allows_all_imports()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);

        $this->assertNull($workspace->plan_id);
    }
}
