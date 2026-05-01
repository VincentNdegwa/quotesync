<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Quotes\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::create([
        'name' => 'Test Workspace',
        'display_name' => 'Test Workspace',
        'owner_id' => $this->user->id,
    ]);
    
    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
    ]);
    
    // Create an inclusive tax
    $this->inclusiveTax = Tax::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'name' => 'Inclusive VAT',
        'rate' => 10,
        'inclusive' => true,
    ]);

    // Create an exclusive tax
    $this->exclusiveTax = Tax::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'name' => 'Exclusive GST',
        'rate' => 10,
        'inclusive' => false,
    ]);
});

test('it persists inclusive and exclusive tax flags when creating a quote', function () {
    $quoteService = app(QuoteService::class);
    
    $payload = [
        'title' => 'Test Quote',
        'client_id' => $this->client->id,
        'status' => 'draft',
        'currency' => 'GBP',
        'created_by' => $this->user->id,
        'sections' => [
            [
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [
                    [
                        'name' => 'Web design',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'discount_percent' => 0,
                        'is_optional' => false,
                        'taxes' => [
                            [
                                'tax_id' => $this->exclusiveTax->id,
                                'tax_label' => 'Exclusive VAT',
                                'tax_rate' => 10,
                                'inclusive' => false,
                            ],
                            [
                                'tax_id' => $this->inclusiveTax->id,
                                'tax_label' => 'Inclusive GST',
                                'tax_rate' => 10,
                                'inclusive' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $quote = $quoteService->create($this->workspace, $payload);
    
    expect($quote)->not->toBeNull();
    
    // Check line item totals (10% inc + 10% exc on 200 should be 220 total)
    $lineItem = $quote->sections->first()->lineItems->first();
    expect((float) $lineItem->total)->toBe(220.0);
    expect((float) $lineItem->tax_amount)->toBe(38.18); // 18.18 (inc) + 20 (exc)

    // Verify tax persistence in database
    $this->assertDatabaseHas('quote_line_item_taxes', [
        'quote_line_item_id' => $lineItem->id,
        'tax_id' => $this->exclusiveTax->id,
        'inclusive' => false,
    ]);

    $this->assertDatabaseHas('quote_line_item_taxes', [
        'quote_line_item_id' => $lineItem->id,
        'tax_id' => $this->inclusiveTax->id,
        'inclusive' => true,
    ]);
});

test('it persists inclusive and exclusive tax flags when updating a quote', function () {
    $quoteService = app(QuoteService::class);
    
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => 'draft',
    ]);

    $payload = [
        'title' => 'Updated Quote',
        'sections' => [
            [
                'title' => 'Updated Section',
                'line_items' => [
                    [
                        'name' => 'Updated Item',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'taxes' => [
                            [
                                'tax_id' => $this->exclusiveTax->id,
                                'tax_label' => 'VAT',
                                'tax_rate' => 10,
                                'inclusive' => false,
                            ],
                            [
                                'tax_id' => $this->inclusiveTax->id,
                                'tax_label' => 'GST',
                                'tax_rate' => 10,
                                'inclusive' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $quoteService->update($quote, $payload);

    $lineItem = $quote->fresh()->sections->first()->lineItems->first();
    
    expect($lineItem->taxes)->toHaveCount(2);
    
    $exclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->exclusiveTax->id)->first();
    $inclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->inclusiveTax->id)->first();
    
    expect((bool) $exclusiveTaxEntry->inclusive)->toBeFalse();
    expect((bool) $inclusiveTaxEntry->inclusive)->toBeTrue();
});
