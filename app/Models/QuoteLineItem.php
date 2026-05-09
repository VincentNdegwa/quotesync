<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'quote_id',
    'quote_section_id',
    'catalog_item_id',
    'catalog_item_variant_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'cost_price',
    'discount_percent',
    'price_tier_applied',
    'subtotal',
    'base_unit_price',
    'base_subtotal',
    'tax_amount',
    'base_tax_amount',
    'total',
    'base_total',
    'is_optional',
    'notes',
    'sort_order',
])]
class QuoteLineItem extends Model
{
    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<QuoteSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(QuoteSection::class, 'quote_section_id');
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return BelongsTo<CatalogItemVariant, $this>
     */
    public function catalogItemVariant(): BelongsTo
    {
        return $this->belongsTo(CatalogItemVariant::class);
    }

    /**
     * @return HasMany<QuoteLineItemTax, $this>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(QuoteLineItemTax::class)->orderBy('id');
    }

    /**
     * Get the computed tax amount as sum of all taxes (in quote currency)
     */
    protected function taxAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->taxes->sum('tax_amount'),
        );
    }

    /**
     * Get the computed base tax amount as sum of all taxes (in base currency)
     */
    protected function baseTaxAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->taxes->sum('base_tax_amount'),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'is_optional' => 'boolean',
        ];
    }
}
