<?php

namespace App\Services\Quotes;

/**
 * Tax calculation utility for handling inclusive and exclusive taxes
 */
class TaxCalculator
{
    /**
     * Calculate line item totals considering inclusive and exclusive taxes
     *
     * Stated Price (baseAmount) is the price entered by the user.
     * 
     * For inclusive tax (tax already included in price):
     * - tax = baseAmount * rate / (100 + rate)
     *
     * For exclusive tax (tax added on top):
     * - tax = baseAmount * rate / 100
     *
     * @param  float  $quantity  Item quantity
     * @param  float  $unitPrice  Item unit price
     * @param  float  $discountPercent  Discount percentage (0-100)
     * @param  array<array{tax_rate: float, inclusive: bool}>  $taxes  Array of tax items
     * @return array{subtotal: float, taxAmount: float, total: float}
     */
    public static function calculateLineItemTotals(
        float $quantity,
        float $unitPrice,
        float $discountPercent,
        array $taxes,
    ): array {
        $qty = max($quantity, 0);
        $price = max($unitPrice, 0);
        $discount = min(max($discountPercent, 0), 100);

        $baseAmount = $qty * $price * (1 - $discount / 100);

        // 1. Calculate inclusive taxes (extracted from the baseAmount)
        $inclusiveTaxAmount = collect($taxes)
            ->filter(fn ($tax) => ($tax['inclusive'] ?? false) === true)
            ->reduce(function ($sum, $tax) use ($baseAmount) {
                $rate = max($tax['tax_rate'] ?? 0, 0);

                return $sum + $baseAmount * $rate / (100 + $rate);
            }, 0);

        // 2. Calculate exclusive taxes (added on top of the baseAmount)
        $exclusiveTaxAmount = collect($taxes)
            ->filter(fn ($tax) => ($tax['inclusive'] ?? false) === false)
            ->reduce(function ($sum, $tax) use ($baseAmount) {
                $rate = max($tax['tax_rate'] ?? 0, 0);

                return $sum + $baseAmount * $rate / 100;
            }, 0);

        // Total Tax is the sum of both
        $taxAmount = $inclusiveTaxAmount + $exclusiveTaxAmount;
        
        // Total is Stated Price + Exclusive Taxes
        $total = $baseAmount + $exclusiveTaxAmount;
        
        // Subtotal is Total - Total Tax (which is also baseAmount - inclusiveTaxAmount)
        $subtotal = $total - $taxAmount;

        return [
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * Calculate individual tax amount for a specific tax
     *
     * @param  float  $baseAmount  Base amount (quantity * unit price * discount factor)
     * @param  float  $taxRate  Tax rate
     * @param  bool  $isInclusive  Whether tax is inclusive
     * @param  float  $inclusiveTaxAmount  Deprecated/Ignored (kept for signature compatibility)
     * @return float
     */
    public static function calculateIndividualTaxAmount(
        float $baseAmount,
        float $taxRate,
        bool $isInclusive,
        float $inclusiveTaxAmount = 0,
    ): float {
        $rate = max($taxRate, 0);

        if ($isInclusive) {
            return $baseAmount * $rate / (100 + $rate);
        }

        return $baseAmount * $rate / 100;
    }
}
