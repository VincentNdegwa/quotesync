<?php

namespace App\Models;

use App\Enums\SignalDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteWinProbabilitySignal extends Model
{
    protected $fillable = [
        'win_probability_id',
        'key',
        'label',
        'probability',
        'weight',
        'sample_size',
        'direction',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'probability' => 'decimal:2',
            'weight' => 'decimal:2',
            'sample_size' => 'integer',
            'direction' => SignalDirection::class,
            'meta' => 'array',
        ];
    }

    public function winProbability(): BelongsTo
    {
        return $this->belongsTo(QuoteWinProbability::class, 'win_probability_id');
    }
}
