# Tax Calculation Implementation Plan

## Overview

Implement proper tax breakdown storage to enable analytics reporting by tax type (VAT, WHT, etc.). Currently, tax amounts are stored as combined sums, preventing per-tax-type analytics.

## Problem Statement

**Current State:**
- `QuoteLineItemTax` stores only tax definition (`tax_label`, `tax_rate`, `inclusive`)
- `QuoteLineItem.tax_amount` stores combined sum of all taxes on that line item
- Cannot query "how much VAT vs WHT was collected"
- Missing base currency fields for multi-currency analytics

**Required State:**
- `QuoteLineItemTax` stores computed `tax_amount` per tax type
- `QuoteLineItem` tax amount becomes computed accessor (sum of related taxes)
- `Quote` stores base currency equivalents for all monetary fields
- Enable tax breakdown analytics queries

---

## Changes Required

### 1. Database Schema Changes

#### QuoteLineItemTax Table
**Add columns:**
- `tax_amount` (decimal 15,2) - computed amount in quote currency
- `base_tax_amount` (decimal 15,2) - computed amount in base currency

#### Quote Table
**Add columns:**
- `base_subtotal` (decimal 15,2) - subtotal in base currency
- `base_discount_amount` (decimal 15,2) - discount amount in base currency
- `base_tax_amount` (decimal 15,2) - tax amount in base currency

#### QuoteLineItem Table
**Remove column:**
- `tax_amount` (will become computed accessor)

---

### 2. Model Changes

#### QuoteLineItemTax Model
**Update fillable array:**
```php
#[Fillable([
    'quote_line_item_id',
    'tax_id',
    'tax_label',
    'tax_rate',
    'inclusive',
    'tax_amount',      // ← add
    'base_tax_amount', // ← add
])]
```

**Update casts:**
```php
protected function casts(): array
{
    return [
        'tax_rate'        => 'decimal:3',
        'inclusive'       => 'boolean',
        'tax_amount'      => 'decimal:2',
        'base_tax_amount' => 'decimal:2',
    ];
}
```

#### Quote Model
**Update fillable array:**
```php
#[Fillable([
    // ... existing fields ...
    'base_subtotal',        // ← add
    'base_discount_amount', // ← add
    'base_tax_amount',      // ← add
])]
```

**Update casts:**
```php
protected function casts(): array
{
    return [
        // ... existing casts ...
        'base_subtotal'        => 'decimal:2',
        'base_discount_amount' => 'decimal:2',
        'base_tax_amount'      => 'decimal:2',
    ];
}
```

#### QuoteLineItem Model
**Remove from fillable:**
- `tax_amount`

**Remove from casts:**
- `tax_amount`

**Add computed accessor:**
```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function taxAmount(): Attribute
{
    return Attribute::make(
        get: fn () => $this->taxes->sum('tax_amount'),
    );
}
```

---

### 3. Service Changes

#### TaxCalculator
**Update `calculateLineItemTotals` to return individual tax amounts:**
```php
public static function calculateLineItemTotals(
    float $quantity,
    float $unitPrice,
    float $discountPercent,
    array $taxes,
): array {
    // ... existing calculation logic ...
    
    // Return individual tax breakdown
    $taxBreakdown = [];
    foreach ($taxes as $tax) {
        $rate = max($tax['tax_rate'] ?? 0, 0);
        $isInclusive = ($tax['inclusive'] ?? false) === true;
        
        if ($isInclusive) {
            $amount = $baseAmount * $rate / (100 + $rate);
        } else {
            $amount = $baseAmount * $rate / 100;
        }
        
        $taxBreakdown[] = [
            'tax_rate' => $rate,
            'inclusive' => $isInclusive,
            'tax_amount' => round($amount, 2),
        ];
    }
    
    return [
        'subtotal' => $subtotal,
        'taxAmount' => $taxAmount,
        'total' => $total,
        'taxBreakdown' => $taxBreakdown, // ← add this
    ];
}
```

#### QuoteService
**Update line item creation to store individual tax amounts:**
```php
// In create/update methods
$calculatedTotals = TaxCalculator::calculateLineItemTotals(
    (float) Arr::get($lineItemData, 'quantity', 1),
    (float) Arr::get($lineItemData, 'unit_price', 0),
    (float) Arr::get($lineItemData, 'discount_percent', 0),
    $taxesArray,
);

$lineItem = $section->lineItems()->create([
    // ... other fields ...
    'subtotal' => $calculatedTotals['subtotal'],
    // 'tax_amount' => $calculatedTotals['taxAmount'], // ← remove this
    'total' => $calculatedTotals['total'],
    // ... other fields ...
]);

// Store individual tax amounts
foreach ($taxes as $index => $taxData) {
    $taxBreakdown = $calculatedTotals['taxBreakdown'][$index] ?? null;
    
    $lineItem->taxes()->create([
        'tax_id' => Arr::get($taxData, 'tax_id'),
        'tax_label' => (string) Arr::get($taxData, 'tax_label', 'Tax'),
        'tax_rate' => (float) Arr::get($taxData, 'tax_rate', 0),
        'inclusive' => (bool) $inclusiveValue,
        'tax_amount' => $taxBreakdown['tax_amount'] ?? 0, // ← add this
        'base_tax_amount' => round(($taxBreakdown['tax_amount'] ?? 0) * $quote->fx_rate, 2), // ← add this
    ]);
}
```

**Update quote totals calculation:**
```php
private function calculateQuoteTotals(Quote $quote): void
{
    $subtotal = 0;
    $discountAmount = 0;
    $taxAmount = 0;

    foreach ($quote->sections as $section) {
        foreach ($section->lineItems as $lineItem) {
            $subtotal += $lineItem->subtotal;
            $discountAmount += ($lineItem->subtotal * $lineItem->discount_percent / 100);
            $taxAmount += $lineItem->taxAmount; // ← uses accessor now
        }
    }

    $total = $subtotal - $discountAmount + $taxAmount;

    // Calculate base currency equivalents
    $baseTotal = $total;
    $baseSubtotal = $subtotal;
    $baseDiscountAmount = $discountAmount;
    $baseTaxAmount = $taxAmount;
    $quoteTotal = null;
    
    if ($quote->fx_rate && $quote->base_currency && $quote->base_currency !== $quote->currency) {
        $quoteTotal = $total / $quote->fx_rate;
        $baseTotal = $total; // Line items are in base currency
        $baseSubtotal = $subtotal;
        $baseDiscountAmount = $discountAmount;
        $baseTaxAmount = $taxAmount;
    } else {
        $quoteTotal = $total;
    }

    $quote->update([
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'tax_amount' => $taxAmount,
        'total' => $quoteTotal,
        'base_total' => $baseTotal,
        'base_subtotal' => $baseSubtotal,        // ← add
        'base_discount_amount' => $baseDiscountAmount, // ← add
        'base_tax_amount' => $baseTaxAmount,      // ← add
    ]);
}
```

**Update duplicate/revise methods to copy tax amounts:**
```php
// In duplicate/revise methods
foreach ($lineItem->taxes as $tax) {
    $newLineItem->taxes()->create([
        'tax_id' => $tax->tax_id,
        'tax_label' => $tax->tax_label,
        'tax_rate' => $tax->tax_rate,
        'inclusive' => (bool) $tax->inclusive,
        'tax_amount' => $tax->tax_amount,      // ← add
        'base_tax_amount' => $tax->base_tax_amount, // ← add
    ]);
}
```

---

### 4. Migration

#### Create Migration File
```bash
php artisan make:migration update_tax_tables_for_breakdown
```

**Migration content:**
```php
public function up(): void
{
    Schema::table('quote_line_item_taxes', function (Blueprint $table) {
        $table->decimal('tax_amount', 15, 2)->default(0)->after('inclusive');
        $table->decimal('base_tax_amount', 15, 2)->default(0)->after('tax_amount');
    });

    Schema::table('quotes', function (Blueprint $table) {
        $table->decimal('base_subtotal', 15, 2)->default(0)->after('base_total');
        $table->decimal('base_discount_amount', 15, 2)->default(0)->after('base_subtotal');
        $table->decimal('base_tax_amount', 15, 2)->default(0)->after('base_discount_amount');
    });

    Schema::table('quote_line_items', function (Blueprint $table) {
        $table->dropColumn('tax_amount');
    });
}

public function down(): void
{
    Schema::table('quote_line_item_taxes', function (Blueprint $table) {
        $table->dropColumn(['tax_amount', 'base_tax_amount']);
    });

    Schema::table('quotes', function (Blueprint $table) {
        $table->dropColumn(['base_subtotal', 'base_discount_amount', 'base_tax_amount']);
    });

    Schema::table('quote_line_items', function (Blueprint $table) {
        $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
    });
}
```

---

### 5. Data Backfill

#### Create Backfill Command
```bash
php artisan make:command BackfillTaxAmounts
```

**Command logic:**
```php
public function handle()
{
    // For each quote
    Quote::with(['sections.lineItems.taxes'])->chunk(100, function ($quotes) {
        foreach ($quotes as $quote) {
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
                            'base_tax_amount' => round($taxAmount * $quote->fx_rate, 2),
                        ]);
                    }
                }
            }
            
            // Recalculate quote totals
            $quote->refresh();
            $this->recalculateQuoteTotals($quote);
        }
    });
}
```

---

### 6. Test Updates

#### Update QuoteTaxPersistenceTest
- Update to verify `tax_amount` stored on `QuoteLineItemTax`
- Verify `base_tax_amount` calculation
- Verify `QuoteLineItem.taxAmount` accessor returns correct sum
- Verify new base currency fields on `Quote`

#### Update TaxCalculator Tests
- Test that `calculateLineItemTotals` returns `taxBreakdown` array
- Test individual tax amount calculations
- Test inclusive vs exclusive tax calculations

#### Add New Test
- Test tax breakdown query for analytics
- Verify per-tax-type aggregation works correctly

---

## Implementation Order

1. ✅ Create this plan document
2. Create migration file
3. Update models (QuoteLineItemTax, Quote, QuoteLineItem)
4. Update TaxCalculator to return individual tax amounts
5. Update QuoteService to store individual tax amounts
6. Update quote totals calculation
7. Update duplicate/revise methods
8. Run migration
9. Create and run backfill command
10. Update tests
11. Run tests
12. Verify analytics queries work

---

## Validation Checklist

- [ ] Migration runs successfully
- [ ] Models updated with new fields
- [ ] TaxCalculator returns individual tax breakdowns
- [ ] QuoteService stores individual tax amounts
- [ ] QuoteLineItem tax_amount accessor works
- [ ] Quote totals include base currency fields
- [ ] Backfill command completes without errors
- [ ] Existing data backfilled correctly
- [ ] All tests pass
- [ ] Tax breakdown query returns correct results
- [ ] Analytics can report by tax type

---

## Analytics Query Example (Post-Implementation)

```sql
-- Tax collected by type, this quarter, won quotes only
SELECT
    qlit.tax_label,
    qlit.tax_rate,
    SUM(qlit.base_tax_amount) as total_collected,
    COUNT(DISTINCT q.id) as quote_count
FROM quote_line_item_taxes qlit
JOIN quote_line_items qli ON qli.id = qlit.quote_line_item_id
JOIN quotes q ON q.id = qli.quote_id
WHERE q.workspace_id = ?
  AND q.status = 'won'
  AND q.won_at BETWEEN ? AND ?
GROUP BY qlit.tax_label, qlit.tax_rate
ORDER BY total_collected DESC;
```

Expected result:
```
VAT (16%)         KES 84,320    45 quotes
WHT (5%)          KES 12,100    23 quotes
Zero Rated (0%)   KES 0         12 quotes
```
