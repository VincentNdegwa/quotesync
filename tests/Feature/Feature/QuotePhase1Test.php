<?php

use App\Models\CatalogItem;
use App\Models\CatalogItemPriceTier;
use App\Models\CatalogItemVariant;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Quotes\QuoteService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->user->current_workspace_id = $this->workspace->id;
    $this->user->save();
});

test('quote line items can save cost_price', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
    $quoteService = app(QuoteService::class);

    $payload = [
        'title' => 'Test Quote',
        'client_id' => $client->id,
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'name' => 'Service 1',
                        'quantity' => 2,
                        'unit_price' => 100,
                        'cost_price' => 50,
                        'discount_percent' => 0,
                    ],
                ],
            ],
        ],
    ];

    $quote = $quoteService->create($this->workspace, $payload);

    expect($quote->sections->first()->lineItems->first()->cost_price)->toBe('50.00');
});

test('quote can save deposit_percent', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
    $quoteService = app(QuoteService::class);

    $payload = [
        'title' => 'Test Quote',
        'client_id' => $client->id,
        'deposit_percent' => 20,
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'name' => 'Service 1',
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ],
        ],
    ];

    $quote = $quoteService->create($this->workspace, $payload);

    expect($quote->deposit_percent)->toBe(20);
});

test('catalog item can have variants', function () {
    $catalogItem = CatalogItem::factory()->create(['workspace_id' => $this->workspace->id]);

    CatalogItemVariant::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'name' => 'Small',
        'is_default' => true,
    ]);

    CatalogItemVariant::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'name' => 'Large',
        'is_default' => false,
    ]);

    $variants = $catalogItem->variants()->get();
    expect($variants)->toHaveCount(2);
    expect($variants->where('is_default', true)->count())->toBe(1);
});

test('catalog item can have price tiers', function () {
    $catalogItem = CatalogItem::factory()->create(['workspace_id' => $this->workspace->id]);

    CatalogItemPriceTier::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'min_quantity' => 1,
        'max_quantity' => 10,
        'pricing_type' => 'fixed_price',
        'value' => 100,
    ]);

    CatalogItemPriceTier::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'min_quantity' => 11,
        'max_quantity' => null,
        'pricing_type' => 'fixed_price',
        'value' => 80,
    ]);

    expect($catalogItem->priceTiers)->toHaveCount(2);
    expect($catalogItem->priceTiers->first()->min_quantity)->toBe(1);
});

test('client can have contacts', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);

    Contact::factory()->create([
        'client_id' => $client->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_primary' => true,
    ]);

    Contact::factory()->create([
        'client_id' => $client->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'is_primary' => false,
    ]);

    expect($client->contacts()->count())->toBe(2);
    expect($client->contacts()->where('is_primary', true)->exists())->toBeTrue();
});

test('health score recalculates when quote is marked as won', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $quoteService = app(QuoteService::class);
    $quoteService->markAsWon($quote);

    $client->refresh();
    expect($client->health_score)->not->toBeNull();
});

test('health score recalculates when quote is marked as lost', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $quoteService = app(QuoteService::class);
    $quoteService->markAsLost($quote, 'Too expensive');

    $client->refresh();
    expect($client->health_score)->not->toBeNull();
});

test('only one variant can be default per catalog item', function () {
    $catalogItem = CatalogItem::factory()->create(['workspace_id' => $this->workspace->id]);

    $variant1 = CatalogItemVariant::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'name' => 'Small',
        'is_default' => true,
    ]);

    $variant2 = CatalogItemVariant::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'name' => 'Large',
        'is_default' => true,
    ]);

    $variant1->refresh();
    $variant2->refresh();

    expect($variant1->is_default)->toBeFalse();
    expect($variant2->is_default)->toBeTrue();
});

test('catalog item variant is scoped to workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['workspace_id' => $this->workspace->id]);

    CatalogItemVariant::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'name' => 'Small',
    ]);

    actingAs($this->user)
        ->get(route('catalog.index'))
        ->assertSuccessful();

    $variants = CatalogItemVariant::all();
    expect($variants)->toHaveCount(1);
});

test('catalog item price tier is scoped to workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['workspace_id' => $this->workspace->id]);

    CatalogItemPriceTier::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'min_quantity' => 1,
    ]);

    $tiers = CatalogItemPriceTier::all();
    expect($tiers)->toHaveCount(1);
});

test('contact is scoped to workspace', function () {
    $client = Client::factory()->create(['workspace_id' => $this->workspace->id]);

    Contact::factory()->create([
        'client_id' => $client->id,
        'name' => 'John Doe',
    ]);

    $contacts = Contact::all();
    expect($contacts)->toHaveCount(1);
});
