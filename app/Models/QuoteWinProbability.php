<?php

namespace App\Models;

use App\Enums\WinProbabilityConfidence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteWinProbability extends Model
{
    protected $fillable = [
        'quote_id',
        'probability',
        'confidence',
        'has_data',
    ];

    protected function casts(): array
    {
        return [
            'probability' => 'decimal:2',
            'confidence' => WinProbabilityConfidence::class,
            'has_data' => 'boolean',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(QuoteWinProbabilitySignal::class, 'win_probability_id');
    }

    public function toResponseArray(): array
    {
        return [
            'probability' => $this->probability ? (int) $this->probability : null,
            'confidence' => $this->confidence->value,
            'signals' => $this->signals->map(fn ($signal) => [
                'key' => $signal->key,
                'label' => $signal->label,
                'probability' => $signal->probability,
                'weight' => $signal->weight,
                'sample_size' => $signal->sample_size,
                'direction' => $signal->direction,
                'meta' => $signal->meta,
            ])->toArray(),
            'has_data' => $this->has_data,
        ];
    }
}
