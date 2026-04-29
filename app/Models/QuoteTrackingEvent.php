<?php

namespace App\Models;

use App\Enums\TrackingEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_id',
    'event_type',
    'duration_seconds',
    'section_name',
    'scroll_depth_percent',
    'ip_address',
    'user_agent',
    'metadata',
    'occurred_at',
])]
class QuoteTrackingEvent extends Model
{
    public $timestamps = false;

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => TrackingEventType::class,
            'duration_seconds' => 'integer',
            'scroll_depth_percent' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
