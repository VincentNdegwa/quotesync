<?php

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\ConfigurationUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a catalog item via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);
    $unit = ConfigurationUnit::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Test Item',
        'description' => 'Test Description',
        'sku' => 'SKU-001',
        'unit_id' => $unit->id,
        'unit_price' => 100.00,
        'catalog_category_id' => $category->id,
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->post(route('catalog.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('catalog_items', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Item',
        'sku' => 'SKU-001',
    ]);
});

it('can update a catalog item via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);
    $unit = ConfigurationUnit::factory()->create(['workspace_id' => $workspace->id]);
    $item = CatalogItem::factory()->create([
        'workspace_id' => $workspace->id,
        'catalog_category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $payload = [
        'name' => 'Updated Item',
        'unit_id' => $unit->id,
        'unit_price' => 150.00,
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->put(route('catalog.update', $item), $payload);

    $response->assertRedirect();

    $item->refresh();
    expect($item->name)->toBe('Updated Item');
});

it('can delete a catalog item via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);
    $item = CatalogItem::factory()->create([
        'workspace_id' => $workspace->id,
        'catalog_category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('catalog.destroy', $item));

    $response->assertRedirect();

    $item->refresh();
    expect($item->deleted_at)->not->toBeNull();
});

it('can perform bulk action on catalog items via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);
    $item1 = CatalogItem::factory()->create([
        'workspace_id' => $workspace->id,
        'catalog_category_id' => $category->id,
        'is_active' => true,
    ]);
    $item2 = CatalogItem::factory()->create([
        'workspace_id' => $workspace->id,
        'catalog_category_id' => $category->id,
        'is_active' => true,
    ]);

    $payload = [
        'ids' => [$item1->id, $item2->id],
        'action' => 'deactivate',
    ];

    $response = $this->actingAs($user)
        ->post(route('catalog.bulk-action'), $payload);

    $response->assertRedirect();

    $item1->refresh();
    $item2->refresh();
    expect($item1->is_active)->toBeFalse();
    expect($item2->is_active)->toBeFalse();
});

it('cannot access catalog item from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $categoryA = CatalogCategory::factory()->create(['workspace_id' => $workspaceA->id]);
    $item = CatalogItem::factory()->create([
        'workspace_id' => $workspaceA->id,
        'catalog_category_id' => $categoryA->id,
    ]);

    $userB = User::factory()->create();

    $response = $this->actingAs($userB)
        ->get(route('catalog.show', $item));

    $response->assertNotFound();
});

it('can create a catalog category via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $payload = [
        'name' => 'Test Category',
        'sort_order' => 1,
    ];

    $response = $this->actingAs($user)
        ->post(route('catalog-categories.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('catalog_categories', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Category',
    ]);
});

it('can update a catalog category via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Updated Category',
        'sort_order' => 2,
    ];

    $response = $this->actingAs($user)
        ->put(route('catalog-categories.update', $category), $payload);

    $response->assertStatus(200);

    $category->refresh();
    expect($category->name)->toBe('Updated Category');
});

it('can delete a catalog category via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $category = CatalogCategory::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('catalog-categories.destroy', $category));

    $response->assertStatus(204);

    $category->refresh();
    expect($category->deleted_at)->not->toBeNull();
});
