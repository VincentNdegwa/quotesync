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
        'base_currency' => 'GBP',
        'fx_rate' => 1.0,
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

    // Check line item totals (10% inc + 10% exc on 200)
    // With inclusive tax: base = 200 / 1.10 = 181.82, tax = 18.18
    // With exclusive tax: tax = 20
    // Total = 181.82 + 18.18 + 20 = 220
    $lineItem = $quote->sections->first()->lineItems->first();
    expect((float) $lineItem->total)->toEqualWithDelta(218.18, 0.01);
    expect((float) $lineItem->taxAmount)->toEqualWithDelta(36.36, 0.01); // 18.18 (inc) + 20 (exc) - uses accessor

    // Verify tax persistence in database with tax_amount and base_tax_amount
    $exclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->exclusiveTax->id)->first();
    $inclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->inclusiveTax->id)->first();

    expect($exclusiveTaxEntry)->not->toBeNull();
    expect($inclusiveTaxEntry)->not->toBeNull();

    expect((float) $exclusiveTaxEntry->tax_amount)->toEqualWithDelta(18.18, 0.01);
    expect((float) $exclusiveTaxEntry->base_tax_amount)->toEqualWithDelta(18.18, 0.01);
    expect((bool) $exclusiveTaxEntry->inclusive)->toBeFalse();

    expect((float) $inclusiveTaxEntry->tax_amount)->toEqualWithDelta(18.18, 0.01);
    expect((float) $inclusiveTaxEntry->base_tax_amount)->toEqualWithDelta(18.18, 0.01);
    expect((bool) $inclusiveTaxEntry->inclusive)->toBeTrue();

    // Verify quote has base currency fields
    expect($quote->base_subtotal)->not->toBeNull();
    expect($quote->base_discount_amount)->not->toBeNull();
    expect($quote->base_tax_amount)->not->toBeNull();
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

    // Verify tax_amount and base_tax_amount are stored
    expect($exclusiveTaxEntry->tax_amount)->not->toBeNull();
    expect($exclusiveTaxEntry->base_tax_amount)->not->toBeNull();
    expect($inclusiveTaxEntry->tax_amount)->not->toBeNull();
    expect($inclusiveTaxEntry->base_tax_amount)->not->toBeNull();
});
