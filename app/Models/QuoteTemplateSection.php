<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quote_template_id',
    'title',
    'sort_order',
])]
class QuoteTemplateSection extends Model
{
    /**
     * @return BelongsTo<QuoteTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'quote_template_id');
    }

    /**
     * @return HasMany<QuoteTemplateLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteTemplateLineItem::class, 'quote_template_section_id')
            ->orderBy('sort_order');
    }
}
