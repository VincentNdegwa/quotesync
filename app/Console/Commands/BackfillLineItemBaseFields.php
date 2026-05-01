<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Models\QuoteLineItem;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:backfill-line-item-base-fields')]
#[Description('Backfill base_* fields for existing quote line items')]
class BackfillLineItemBaseFields extends Command
{
    public function handle()
    {
        $this->info('Backfilling base_* fields for quote line items...');

        $quotes = Quote::query()
            ->whereNotNull('fx_rate')
            ->where('fx_rate', '>', 1)
            ->with(['sections.lineItems'])
            ->get();

        foreach ($quotes as $quote) {
            $fxRate = $quote->fx_rate;

            foreach ($quote->sections as $section) {
                foreach ($section->lineItems as $lineItem) {
                    // Reset all data to original base currency state
                    // Calculate how many times it was converted
                    $conversionCount = 0;
                    $tempUnitPrice = $lineItem->unit_price;
                    while ($tempUnitPrice > 1000 && $conversionCount < 5) {
                        $tempUnitPrice /= $fxRate;
                        $conversionCount++;
                    }

                    // Reset by dividing by fx_rate^conversionCount
                    $resetFactor = $fxRate ** $conversionCount;
                    $lineItem->update([
                        'unit_price' => $lineItem->unit_price / $resetFactor,
                        'subtotal' => $lineItem->subtotal / $resetFactor,
                        'total' => $lineItem->total / $resetFactor,
                        'base_unit_price' => 0,
                        'base_subtotal' => 0,
                        'base_tax_amount' => 0,
                        'base_total' => 0,
                    ]);

                    foreach ($lineItem->taxes as $tax) {
                        $tax->update([
                            'tax_amount' => $tax->tax_amount / $resetFactor,
                            'base_tax_amount' => 0,
                        ]);
                    }

                    // Now apply correct conversion
                    $baseUnitPrice = $lineItem->unit_price;
                    $baseSubtotal = $lineItem->subtotal;
                    $baseTotal = $lineItem->total;

                    // Update taxes first
                    $totalBaseTaxAmount = 0;
                    $totalTaxAmount = 0;
                    foreach ($lineItem->taxes as $tax) {
                        $baseTaxAmount = $tax->tax_amount; // Existing is in base currency
                        $totalBaseTaxAmount += $baseTaxAmount;
                        $totalTaxAmount += $baseTaxAmount * $fxRate;
                        $tax->update([
                            'base_tax_amount' => $baseTaxAmount,
                            'tax_amount' => $baseTaxAmount * $fxRate, // Convert to quote currency
                        ]);
                    }

                    $lineItem->update([
                        'base_unit_price' => $baseUnitPrice,
                        'base_subtotal' => $baseSubtotal,
                        'base_total' => $baseTotal,
                        'base_tax_amount' => $totalBaseTaxAmount,
                        // Convert to quote currency (KES)
                        'unit_price' => $baseUnitPrice * $fxRate,
                        'subtotal' => $baseSubtotal * $fxRate,
                        'total' => $baseTotal * $fxRate,
                    ]);
                }
            }
        }

        $this->info("Backfilled {$quotes->count()} quotes with base_* fields.");
        $this->info('Done.');
    }
}
