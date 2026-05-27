<?php

use App\Services\Quotes\TaxCalculator;

test('it calculates inclusive and exclusive taxes correctly from stated price', function () {
    $quantity = 1;
    $unitPrice = 200;
    $discountType = null;
    $discountValue = 0;
    $taxes = [
        ['tax_rate' => 10, 'inclusive' => true],
        ['tax_rate' => 10, 'inclusive' => false],
    ];

    $result = TaxCalculator::calculateLineItemTotals($quantity, $unitPrice, $discountType, $discountValue, $taxes);

    // Stated Price = 200
    // Inclusive Tax (10%) = 200 * 10 / 110 = 18.1818...
    // Net Price = 200 - 18.1818... = 181.8181...
    // Exclusive Tax (10%) = 181.8181... * 10 / 100 = 18.1818...
    // Total Tax = 18.1818... + 18.1818... = 36.3636...
    // Total = Stated Price + Exclusive Tax = 200 + 18.1818... = 218.1818...
    // Subtotal = Net Price = 181.8181...

    expect($result['total'])->toEqualWithDelta(218.18, 0.01);
    expect($result['taxAmount'])->toEqualWithDelta(36.36, 0.01);
    expect($result['subtotal'])->toEqualWithDelta(181.82, 0.01);
    expect($result)->toHaveKey('taxBreakdown');
    expect($result['taxBreakdown'])->toHaveCount(2);
    expect($result['taxBreakdown'][0]['tax_amount'])->toEqualWithDelta(18.18, 0.01);
    expect($result['taxBreakdown'][1]['tax_amount'])->toEqualWithDelta(18.18, 0.01);
});

test('it handles only inclusive tax', function () {
    $result = TaxCalculator::calculateLineItemTotals(1, 100, null, 0, [
        ['tax_rate' => 20, 'inclusive' => true],
    ]);

    // 100 * 20 / 120 = 16.67
    expect($result['total'])->toEqualWithDelta(100.0, 0.01);
    expect($result['taxAmount'])->toEqualWithDelta(16.67, 0.01);
    expect($result['subtotal'])->toEqualWithDelta(83.33, 0.01);
});

test('it handles only exclusive tax', function () {
    $result = TaxCalculator::calculateLineItemTotals(1, 100, null, 0, [
        ['tax_rate' => 20, 'inclusive' => false],
    ]);

    // 100 * 20 / 100 = 20
    expect($result['total'])->toBe(120.0);
    expect($result['taxAmount'])->toBe(20.0);
    expect($result['subtotal'])->toBe(100.0);
});
