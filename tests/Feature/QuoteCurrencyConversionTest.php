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
        'currency' => 'GBP',
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

test('it correctly calculates quote with currency conversion, multiple taxes, and discount', function () {
    $quoteService = app(QuoteService::class);
    
    // Scenario: Workspace in GBP, Quote in KES with fx_rate 174.75
    // Item: 200 GBP, 10% discount
    // Taxes: 10% inclusive (GST), 10% exclusive (VAT)
    
    $payload = [
        'title' => 'Test Quote Currency Conversion',
        'client_id' => $this->client->id,
        'status' => 'draft',
        'currency' => 'KES',
        'base_currency' => 'GBP',
        'fx_rate' => 174.75,
        'created_by' => $this->user->id,
        'sections' => [
            [
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [
                    [
                        'name' => 'Web design',
                        'quantity' => 1,
                        'unit_price' => 200, // in GBP (base currency)
                        'discount_percent' => 10,
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
    expect($quote->currency)->toBe('KES');
    expect($quote->base_currency)->toBe('GBP');
    expect((float) $quote->fx_rate)->toBe(174.75);
    
    $lineItem = $quote->sections->first()->lineItems->first();
    
    // Expected calculations in GBP (base currency):
    // unit_price: 200 GBP
    // discount: 10% = 20 GBP
    // after discount: 180 GBP
    // GST (inclusive 10%): 180 * 10 / 110 = 16.3636... GBP
    // VAT (exclusive 10%): 180 * 10 / 100 = 18 GBP
    // subtotal (net of inclusive tax): 180 - 16.36 = 163.64 GBP
    // total tax: 16.36 + 18 = 34.36 GBP
    // line item total: 180 + 18 = 198 GBP (subtotal + exclusive tax)
    
    expect((float) $lineItem->subtotal)->toEqualWithDelta(163.64, 0.01);
    expect((float) $lineItem->total)->toEqualWithDelta(198.00, 0.01);
    expect((float) $lineItem->taxAmount)->toEqualWithDelta(34.36, 0.01);
    
    // Verify individual tax amounts in GBP
    $exclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->exclusiveTax->id)->first();
    $inclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->inclusiveTax->id)->first();
    
    expect((float) $exclusiveTaxEntry->tax_amount)->toBe(18.00);
    expect((float) $inclusiveTaxEntry->tax_amount)->toEqualWithDelta(16.36, 0.01);
    
    // Verify base_tax_amount (should be same as tax_amount, in base currency GBP)
    expect((float) $exclusiveTaxEntry->base_tax_amount)->toBe(18.00);
    expect((float) $inclusiveTaxEntry->base_tax_amount)->toEqualWithDelta(16.36, 0.01);
    
    // Verify quote totals
    // Line items are in base currency (GBP)
    // base_* fields are in base currency (GBP)
    // Non-base fields (subtotal, tax_amount, discount_amount, total) are in quote currency (KES)
    // base_total (GBP): 198.00
    // subtotal (KES): 163.64 * 174.75 = 28,596.09 KES
    // discount_amount (KES): 20.00 * 174.75 = 3,495.00 KES
    // tax_amount (KES): 34.36 * 174.75 = 6,004.41 KES
    // total (KES): 198.00 * 174.75 = 34,600.50 KES
    expect((float) $quote->subtotal)->toEqualWithDelta(28596.09, 0.01);
    expect((float) $quote->tax_amount)->toEqualWithDelta(6004.41, 0.01);
    expect((float) $quote->discount_amount)->toEqualWithDelta(3495.00, 0.01);
    expect((float) $quote->base_total)->toEqualWithDelta(198.00, 0.01);
    expect((float) $quote->total)->toEqualWithDelta(34600.50, 0.01);
    
    // Verify base currency fields (all in GBP)
    expect((float) $quote->base_subtotal)->toEqualWithDelta(163.64, 0.01);
    expect((float) $quote->base_tax_amount)->toEqualWithDelta(34.36, 0.01); // Same as tax_amount in base currency
    expect((float) $quote->base_discount_amount)->toEqualWithDelta(20.00, 0.01);
});

test('it correctly updates quote with currency conversion and taxes', function () {
    $quoteService = app(QuoteService::class);
    
    // Create initial quote
    $createPayload = [
        'title' => 'Test Quote',
        'client_id' => $this->client->id,
        'status' => 'draft',
        'currency' => 'KES',
        'base_currency' => 'GBP',
        'fx_rate' => 174.75,
        'created_by' => $this->user->id,
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'name' => 'Web design',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'discount_percent' => 0,
                        'taxes' => [
                            [
                                'tax_id' => $this->exclusiveTax->id,
                                'tax_label' => 'VAT',
                                'tax_rate' => 10,
                                'inclusive' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $quote = $quoteService->create($this->workspace, $createPayload);
    
    // Update with different values
    $updatePayload = [
        'title' => 'Updated Quote',
        'fx_rate' => 174.75,
        'sections' => [
            [
                'title' => 'Updated Services',
                'line_items' => [
                    [
                        'name' => 'Updated Web design',
                        'quantity' => 2,
                        'unit_price' => 200,
                        'discount_percent' => 10,
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

    $quoteService->update($quote, $updatePayload);

    $quote->refresh();
    $lineItem = $quote->sections->first()->lineItems->first();
    
    // Expected:
    // quantity: 2, unit_price: 200, discount: 10%
    // after discount: 200 * 2 * 0.9 = 360 GBP
    // VAT (exclusive 10%): 360 * 0.10 = 36 GBP
    // GST (inclusive 10%): 360 * 10 / 110 = 32.73 GBP
    // subtotal: 360 - 32.73 = 327.27 GBP
    // total tax: 36 + 32.73 = 68.73 GBP
    // line item total: 360 + 36 = 396 GBP
    // discount_amount: 200 * 2 * 10% = 40 GBP
    // Quote totals in KES (quote currency):
    // subtotal: 327.27 * 174.75 = 57,190.43 KES
    // discount_amount: 40 * 174.75 = 6,990.00 KES
    // tax_amount: 68.73 * 174.75 = 12,001.98 KES
    // total: 396 * 174.75 = 69,201 KES
    // Quote totals in GBP (base currency):
    // base_total: 396 GBP
    
    expect((float) $lineItem->subtotal)->toEqualWithDelta(327.27, 0.01);
    expect((float) $lineItem->total)->toEqualWithDelta(396.00, 0.01);
    expect((float) $lineItem->taxAmount)->toEqualWithDelta(68.73, 0.01);
    
    expect((float) $quote->subtotal)->toEqualWithDelta(57190.43, 0.01);
    expect((float) $quote->tax_amount)->toEqualWithDelta(12010.57, 0.01);
    expect((float) $quote->discount_amount)->toEqualWithDelta(6990.00, 0.01);
    expect((float) $quote->base_total)->toEqualWithDelta(396.00, 0.01);
    expect((float) $quote->total)->toEqualWithDelta(69201.00, 0.01);
});

test('it correctly handles quote without currency conversion (same currency)', function () {
    $quoteService = app(QuoteService::class);
    
    $payload = [
        'title' => 'Test Quote Same Currency',
        'client_id' => $this->client->id,
        'status' => 'draft',
        'currency' => 'GBP',
        'base_currency' => 'GBP',
        'fx_rate' => 1.0,
        'created_by' => $this->user->id,
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'name' => 'Web design',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'discount_percent' => 10,
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

    $quote = $quoteService->create($this->workspace, $payload);
    
    $lineItem = $quote->sections->first()->lineItems->first();
    
    // When currencies are the same, no conversion should happen
    expect((float) $lineItem->subtotal)->toEqualWithDelta(163.64, 0.01);
    expect((float) $lineItem->total)->toEqualWithDelta(198.00, 0.01);
    
    expect((float) $quote->subtotal)->toEqualWithDelta(163.64, 0.01);
    expect((float) $quote->total)->toEqualWithDelta(198.00, 0.01);
    expect((float) $quote->base_total)->toEqualWithDelta(198.00, 0.01);
});

test('it correctly calculates base_tax_amount as sum of tax base_tax_amounts', function () {
    $quoteService = app(QuoteService::class);
    
    $payload = [
        'title' => 'Test Quote Base Tax Amount',
        'client_id' => $this->client->id,
        'status' => 'draft',
        'currency' => 'KES',
        'base_currency' => 'GBP',
        'fx_rate' => 174.75,
        'created_by' => $this->user->id,
        'sections' => [
            [
                'title' => 'Services',
                'line_items' => [
                    [
                        'name' => 'Web design',
                        'quantity' => 1,
                        'unit_price' => 200,
                        'discount_percent' => 10,
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

    $quote = $quoteService->create($this->workspace, $payload);
    
    $lineItem = $quote->sections->first()->lineItems->first();
    
    // base_tax_amount should be sum of tax tax_amounts (both in base currency GBP)
    $exclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->exclusiveTax->id)->first();
    $inclusiveTaxEntry = $lineItem->taxes->where('tax_id', $this->inclusiveTax->id)->first();
    
    $expectedBaseTaxAmount = $exclusiveTaxEntry->tax_amount + $inclusiveTaxEntry->tax_amount;
    
    expect((float) $lineItem->baseTaxAmount)->toEqualWithDelta($expectedBaseTaxAmount, 0.01);
    expect((float) $quote->base_tax_amount)->toEqualWithDelta($expectedBaseTaxAmount, 0.01);
});
