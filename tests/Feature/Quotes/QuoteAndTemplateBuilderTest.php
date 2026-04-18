<?php

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\User;

test('authenticated workspace user can create a quote with nested sections and line items', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $client = Client::factory()->for($workspace, 'workspace')->create();
    $catalogItem = CatalogItem::factory()->for($workspace, 'workspace')->create();
    $tax = Tax::factory()->for($workspace, 'workspace')->create();

    $response = $this->actingAs($user)->post('/quotes', [
        'title' => 'Electrical Installation Proposal',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'valid_until' => now()->addDays(30)->toDateString(),
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'catalog_item_id' => $catalogItem->id,
                        'name' => 'Wiring and installation',
                        'description' => 'Full wiring package',
                        'quantity' => 2,
                        'unit' => 'unit',
                        'unit_price' => 450,
                        'discount_percent' => 0,
                        'subtotal' => 900,
                        'tax_amount' => 67.5,
                        'total' => 967.5,
                        'taxes' => [
                            [
                                'tax_id' => $tax->id,
                                'tax_label' => $tax->name,
                                'tax_rate' => $tax->rate,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $quote = Quote::query()->latest('id')->first();

    expect($quote)->not->toBeNull();

    $response
        ->assertRedirect(route('quotes.edit', $quote));

    $this->assertDatabaseHas('quotes', [
        'id' => $quote?->id,
        'workspace_id' => $workspace->id,
        'title' => 'Electrical Installation Proposal',
        'client_id' => $client->id,
    ]);

    $this->assertDatabaseHas('quote_sections', [
        'quote_id' => $quote?->id,
        'title' => 'Services',
    ]);

    $this->assertDatabaseHas('quote_line_items', [
        'quote_id' => $quote?->id,
        'name' => 'Wiring and installation',
        'catalog_item_id' => $catalogItem->id,
    ]);
});

test('authenticated workspace manager can create quote templates', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $catalogItem = CatalogItem::factory()->for($workspace, 'workspace')->create();
    $tax = Tax::factory()->for($workspace, 'workspace')->create();

    $response = $this->actingAs($user)->post('/quote-templates', [
        'name' => 'Standard Electrical Template',
        'description' => 'Reusable quote skeleton for residential work',
        'industry' => 'Construction',
        'is_active' => true,
        'sections' => [
            [
                'title' => 'Materials',
                'line_items' => [
                    [
                        'catalog_item_id' => $catalogItem->id,
                        'name' => 'Material package',
                        'description' => 'Default material allocation',
                        'quantity' => 1,
                        'unit' => 'lot',
                        'unit_price' => 1200,
                        'discount_percent' => 0,
                        'is_optional' => false,
                        'taxes' => [
                            [
                                'tax_id' => $tax->id,
                                'tax_label' => $tax->name,
                                'tax_rate' => $tax->rate,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $template = QuoteTemplate::query()->latest('id')->first();

    expect($template)->not->toBeNull();

    $response
        ->assertRedirect(route('quote-templates.edit', $template));

    $this->assertDatabaseHas('quote_templates', [
        'id' => $template?->id,
        'workspace_id' => $workspace->id,
        'name' => 'Standard Electrical Template',
        'industry' => 'Construction',
    ]);

    $this->assertDatabaseHas('quote_template_sections', [
        'quote_template_id' => $template?->id,
        'title' => 'Materials',
    ]);

    $this->assertDatabaseHas('quote_template_line_items', [
        'quote_template_id' => $template?->id,
        'name' => 'Material package',
        'catalog_item_id' => $catalogItem->id,
    ]);
});
