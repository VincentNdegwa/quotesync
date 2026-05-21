<?php

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ConfigurationTag;
use App\Models\ConfigurationUnit;
use App\Models\Tax;
use App\Models\User;

test('clients can sync configuration tags via client_tags pivot', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $firstTag = ConfigurationTag::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $secondTag = ConfigurationTag::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/clients', [
            'company_name' => 'Acme Ltd',
            'tag_ids' => [$firstTag->id, $secondTag->id],
        ])
        ->assertRedirect();

    $client = Client::query()->where('workspace_id', $workspace?->id)->first();

    expect($client)->not->toBeNull();
    expect($client?->tags()->pluck('configuration_tags.id')->all())
        ->toEqualCanonicalizing([$firstTag->id, $secondTag->id]);

    $this->actingAs($user)
        ->put('/clients/'.$client?->id, [
            'company_name' => 'Acme Ltd',
            'tag_ids' => [$secondTag->id],
        ])
        ->assertRedirect();

    expect($client?->fresh()?->tags()->pluck('configuration_tags.id')->all())
        ->toEqualCanonicalizing([$secondTag->id]);
});

test('catalog items can sync multiple taxes via pivot table', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $category = CatalogCategory::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $unit = ConfigurationUnit::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $firstTax = Tax::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $secondTax = Tax::factory()->create([
        'workspace_id' => $workspace?->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/catalog', [
            'name' => 'Consultation',
            'unit_id' => $unit->id,
            'unit_price' => 100,
            'cost_price' => 40,
            'catalog_category_id' => $category->id,
            'tax_ids' => [$firstTax->id, $secondTax->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $item = CatalogItem::query()->where('workspace_id', $workspace?->id)->first();

    expect($item)->not->toBeNull();
    expect($item?->taxes()->pluck('taxes.id')->all())
        ->toEqualCanonicalizing([$firstTax->id, $secondTax->id]);

    $this->actingAs($user)
        ->put('/catalog/'.$item?->id, [
            'name' => 'Consultation',
            'unit_id' => $unit->id,
            'unit_price' => 100,
            'cost_price' => 40,
            'catalog_category_id' => $category->id,
            'tax_ids' => [$secondTax->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($item?->fresh()?->taxes()->pluck('taxes.id')->all())
        ->toEqualCanonicalizing([$secondTax->id]);
});
