<?php

namespace App\Models;

use App\Enums\QuoteFollowUpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_id',
    'follow_up_step_id',
    'scheduled_at',
    'sent_at',
    'cancelled_at',
    'status',
])]
class QuoteFollowUp extends Model
{
    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<FollowUpStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(FollowUpStep::class, 'follow_up_step_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteFollowUpStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
