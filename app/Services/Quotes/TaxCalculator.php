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
     * @param  string|null  $discountType  Discount type: 'percent' or 'fixed'
     * @param  float  $discountValue  Discount value (percentage or fixed amount)
     * @param  array<array{tax_rate: float, inclusive: bool}>  $taxes  Array of tax items
     * @return array{subtotal: float, taxAmount: float, total: float, taxBreakdown: array}
     */
    public static function calculateLineItemTotals(
        float $quantity,
        float $unitPrice,
        ?string $discountType,
        float $discountValue,
        array $taxes,
    ): array {
        $qty = max($quantity, 0);
        $price = max($unitPrice, 0);

        // Calculate discount amount based on type
        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discount = min(max($discountValue, 0), 100);
            $discountAmount = $qty * $price * $discount / 100;
        } elseif ($discountType === 'fixed') {
            $discountAmount = min(max($discountValue, 0), $qty * $price);
        }

        $baseAmount = $qty * $price - $discountAmount;

        // 1. Calculate inclusive taxes (extracted from the baseAmount)
        $inclusiveTaxAmount = collect($taxes)
            ->filter(fn ($tax) => ($tax['inclusive'] ?? false) === true)
            ->reduce(function ($sum, $tax) use ($baseAmount) {
                $rate = max($tax['tax_rate'] ?? 0, 0);

                return $sum + $baseAmount * $rate / (100 + $rate);
            }, 0);

        // 2. Net price after extracting inclusive taxes
        $netPrice = $baseAmount - $inclusiveTaxAmount;

        // 3. Calculate exclusive taxes (applied to net price, not baseAmount)
        $exclusiveTaxAmount = collect($taxes)
            ->filter(fn ($tax) => ($tax['inclusive'] ?? false) === false)
            ->reduce(function ($sum, $tax) use ($netPrice) {
                $rate = max($tax['tax_rate'] ?? 0, 0);

                return $sum + $netPrice * $rate / 100;
            }, 0);

        // Calculate individual tax amounts for each tax type
        $taxBreakdown = [];
        foreach ($taxes as $tax) {
            $rate = max($tax['tax_rate'] ?? 0, 0);
            $isInclusive = ($tax['inclusive'] ?? false) === true;

            if ($isInclusive) {
                $taxAmount = $baseAmount * $rate / (100 + $rate);
            } else {
                $taxAmount = $netPrice * $rate / 100;
            }

            $taxBreakdown[] = [
                'tax_rate' => $rate,
                'inclusive' => $isInclusive,
                'tax_amount' => round($taxAmount, 2),
            ];
        }

        // 4. Final Calculations
        // Total Tax is the sum of both
        $taxAmount = $inclusiveTaxAmount + $exclusiveTaxAmount;

        // Total is Stated Price + Exclusive Taxes
        $total = $baseAmount + $exclusiveTaxAmount;

        // Subtotal is net price (baseAmount - inclusive taxes)
        $subtotal = $netPrice;

        return [
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'taxBreakdown' => $taxBreakdown,
        ];
    }

    /**
     * Calculate individual tax amount for a specific tax
     *
     * @param  float  $baseAmount  Base amount (quantity * unit price * discount factor)
     * @param  float  $taxRate  Tax rate
     * @param  bool  $isInclusive  Whether tax is inclusive
     * @param  float  $inclusiveTaxAmount  Deprecated/Ignored (kept for signature compatibility)
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
