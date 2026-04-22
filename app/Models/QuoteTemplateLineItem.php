<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quote_template_id',
    'quote_template_section_id',
    'catalog_item_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'discount_percent',
    'is_optional',
    'notes',
    'sort_order',
])]
class QuoteTemplateLineItem extends Model
{
    /**
     * @return BelongsTo<QuoteTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'quote_template_id');
    }

    /**
     * @return BelongsTo<QuoteTemplateSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplateSection::class, 'quote_template_section_id');
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return HasMany<QuoteTemplateLineItemTax, $this>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(QuoteTemplateLineItemTax::class, 'quote_template_line_item_id');
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
            'is_optional' => 'boolean',
        ];
    }
}
