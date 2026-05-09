<?php

namespace App\Models;

use App\Enums\FollowUpChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'follow_up_sequence_id',
    'day_offset',
    'channel',
    'subject',
    'message_template',
    'sort_order',
])]
class FollowUpStep extends Model
{
    /**
     * @return BelongsTo<FollowUpSequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowUpSequence::class, 'follow_up_sequence_id');
    }

    /**
     * @return HasMany<QuoteFollowUp, $this>
     */
    public function quoteFollowUps(): HasMany
    {
        return $this->hasMany(QuoteFollowUp::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_offset' => 'integer',
            'channel' => FollowUpChannel::class,
            'sort_order' => 'integer',
        ];
    }
}
