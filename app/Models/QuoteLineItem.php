<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quote_id',
    'quote_section_id',
    'catalog_item_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'discount_percent',
    'subtotal',
    'tax_amount',
    'total',
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
     * @return HasMany<QuoteLineItemTax, $this>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(QuoteLineItemTax::class)->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_optional' => 'boolean',
        ];
    }
}
