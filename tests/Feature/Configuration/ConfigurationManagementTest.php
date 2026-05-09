<?php

use App\Models\CatalogCategory;
use App\Models\ConfigurationTag;
use App\Models\ConfigurationUnit;
use App\Models\Tax;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('configuration routes render pages and root redirects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/configuration')
        ->assertRedirect('/configuration/taxes');

    $this->actingAs($user)
        ->get('/configuration/taxes')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('configuration/taxes/Index'));

    $this->actingAs($user)
        ->get('/configuration/categories')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('configuration/categories/Index'));

    $this->actingAs($user)
        ->get('/configuration/tags')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('configuration/tags/Index'));

    $this->actingAs($user)
        ->get('/configuration/units')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('configuration/units/Index'));
});

test('configuration entities can be created updated and deleted', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $this->actingAs($user)
        ->post('/configuration/taxes', [
            'name' => 'VAT',
            'rate' => 16,
            'inclusive' => false,
            'is_default' => true,
            'is_active' => true,
        ])
        ->assertRedirect();

    $tax = Tax::query()->where('workspace_id', $workspace?->id)->where('name', 'VAT')->first();
    expect($tax)->not->toBeNull();

    $this->actingAs($user)
        ->post('/configuration/categories', [
            'name' => 'Materials',
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect();

    $category = CatalogCategory::query()->where('workspace_id', $workspace?->id)->where('name', 'Materials')->first();
    expect($category)->not->toBeNull();

    $this->actingAs($user)
        ->post('/configuration/tags', [
            'name' => 'Priority',
            'is_active' => true,
        ])
        ->assertRedirect();

    $tag = ConfigurationTag::query()->where('workspace_id', $workspace?->id)->where('name', 'Priority')->first();
    expect($tag)->not->toBeNull();

    $this->actingAs($user)
        ->put('/configuration/tags/'.$tag?->id, [
            'name' => 'High Priority',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('configuration_tags', [
        'id' => $tag?->id,
        'workspace_id' => $workspace?->id,
        'name' => 'High Priority',
    ]);

    $this->actingAs($user)
        ->post('/configuration/units', [
            'name' => 'Hour',
            'symbol' => 'hr',
            'is_active' => true,
        ])
        ->assertRedirect();

    $unit = ConfigurationUnit::query()->where('workspace_id', $workspace?->id)->where('name', 'Hour')->first();
    expect($unit)->not->toBeNull();

    $this->actingAs($user)
        ->put('/configuration/units/'.$unit?->id, [
            'name' => 'Hours',
            'symbol' => 'hrs',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('configuration_units', [
        'id' => $unit?->id,
        'workspace_id' => $workspace?->id,
        'name' => 'Hours',
    ]);

    $this->actingAs($user)
        ->delete('/configuration/tags/'.$tag?->id)
        ->assertRedirect();

    $this->actingAs($user)
        ->delete('/configuration/units/'.$unit?->id)
        ->assertRedirect();

    $this->assertSoftDeleted('configuration_tags', ['id' => $tag?->id]);
    $this->assertSoftDeleted('configuration_units', ['id' => $unit?->id]);
});
