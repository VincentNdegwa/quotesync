<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_line_item_id',
    'tax_id',
    'tax_label',
    'tax_rate',
    'inclusive',
])]
class QuoteLineItemTax extends Model
{
    /**
     * @return BelongsTo<QuoteLineItem, $this>
     */
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(QuoteLineItem::class, 'quote_line_item_id');
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
