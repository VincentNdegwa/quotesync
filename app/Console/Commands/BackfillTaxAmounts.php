<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\Quotes\TaxCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-tax-amounts')]
#[Description('Backfill tax_amount and base_tax_amount for existing quote line item taxes')]
class BackfillTaxAmounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting tax amount backfill...');

        Quote::with(['sections.lineItems.taxes'])->chunk(100, function ($quotes) {
            foreach ($quotes as $quote) {
                $this->info("Processing quote #{$quote->id}");

                // Recalculate tax amounts for each line item tax
                foreach ($quote->sections as $section) {
                    foreach ($section->lineItems as $lineItem) {
                        $baseAmount = $lineItem->quantity * $lineItem->unit_price * 
                                       (1 - $lineItem->discount_percent / 100);
                        
                        foreach ($lineItem->taxes as $tax) {
                            $rate = $tax->tax_rate;
                            $isInclusive = $tax->inclusive;
                            
                            if ($isInclusive) {
                                $taxAmount = $baseAmount * $rate / (100 + $rate);
                            } else {
                                $taxAmount = $baseAmount * $rate / 100;
                            }
                            
                            $tax->update([
                                'tax_amount' => round($taxAmount, 2),
                                'base_tax_amount' => round($taxAmount, 2), // Same as tax_amount (base currency)
                            ]);
                        }
                    }
                }
                
                // Recalculate quote totals
                $quote->refresh();
                $this->recalculateQuoteTotals($quote);
            }
        });

        $this->info('Tax amount backfill completed successfully!');
    }

    private function recalculateQuoteTotals(Quote $quote): void
    {
        $subtotal = 0;
        $discountAmount = 0;
        $taxAmount = 0;

        foreach ($quote->sections as $section) {
            foreach ($section->lineItems as $lineItem) {
                if ($lineItem->is_optional) {
                    continue;
                }

                $subtotal += $lineItem->subtotal;
                // Calculate discount from original amount: quantity * unit_price * discount_percent
                $discountAmount += ($lineItem->quantity * $lineItem->unit_price * $lineItem->discount_percent / 100);
                $taxAmount += $lineItem->taxAmount;
            }
        }

        $total = $subtotal + $taxAmount;

        // Line items are in base currency (workspace currency)
        // base_* fields are in base currency (GBP)
        $baseTotal = $total;
        $baseSubtotal = $subtotal;
        $baseDiscountAmount = $discountAmount;
        $baseTaxAmount = 0;

        foreach ($quote->sections as $section) {
            foreach ($section->lineItems as $lineItem) {
                if ($lineItem->is_optional) {
                    continue;
                }
                $baseTaxAmount += $lineItem->taxAmount; // Use taxAmount (base currency)
            }
        }

        // Convert to quote currency (KES)
        // Fields without base_ prefix should be in quote currency
        $quoteSubtotal = $subtotal;
        $quoteDiscountAmount = $discountAmount;
        $quoteTaxAmount = $taxAmount;
        $quoteTotal = $total;

        if ($quote->fx_rate && $quote->base_currency && $quote->base_currency !== $quote->currency) {
            $quoteSubtotal = $subtotal * $quote->fx_rate;
            $quoteDiscountAmount = $discountAmount * $quote->fx_rate;
            $quoteTaxAmount = $taxAmount * $quote->fx_rate;
            $quoteTotal = $total * $quote->fx_rate;
        }

        $quote->update([
            'subtotal' => $quoteSubtotal,
            'discount_amount' => $quoteDiscountAmount,
            'tax_amount' => $quoteTaxAmount,
            'total' => $quoteTotal,
            'base_total' => $baseTotal,
            'base_subtotal' => $baseSubtotal,
            'base_discount_amount' => $baseDiscountAmount,
            'base_tax_amount' => $baseTaxAmount,
        ]);
    }
}
