<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_template_line_item_id',
    'tax_id',
    'tax_label',
    'tax_rate',
    'inclusive',
])]
class QuoteTemplateLineItemTax extends Model
{
    /**
     * @return BelongsTo<QuoteTemplateLineItem, $this>
     */
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplateLineItem::class, 'quote_template_line_item_id');
    }

    /**
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:3',
            'inclusive' => 'boolean',
        ];
    }
}
