<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'workspace_id',
    'user_id',
    'commentable_type',
    'commentable_id',
    'content',
    'mentions',
    'is_internal',
])]
class Comment extends Model
{
    protected function casts(): array
    {
        return [
            'mentions' => 'array',
            'is_internal' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
