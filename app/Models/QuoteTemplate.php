<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'workspace_id',
    'name',
    'description',
    'industry',
    'cover_message',
    'notes',
    'terms',
    'layout',
    'is_active',
    'is_system',
    'usage_count',
    'created_by',
])]
class QuoteTemplate extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $workspaceId = Auth::user()?->current_workspace_id;

            if ($workspaceId !== null) {
                $query->where('workspace_id', $workspaceId);
            }
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<QuoteTemplateSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(QuoteTemplateSection::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuoteTemplateLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteTemplateLineItem::class)->orderBy('sort_order');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'layout' => 'array',
        ];
    }
}
