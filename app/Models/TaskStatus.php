<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'color',
        'sort_order',
        'is_default',
        'is_system',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $status) {
            if ($status->is_system) {
                throw new \Exception('Cannot delete system statuses (To Do and Done).');
            }
        });

        static::updating(function (self $status) {
            // Allow updating sort_order, but prevent changing name, slug, or is_system for system statuses
            if ($status->is_system && $status->isDirty(['name', 'slug', 'is_system'])) {
                throw new \Exception('Cannot edit name, slug, or system flag of system statuses (To Do and Done).');
            }
        });
    }

    public function isSystemStatus(): bool
    {
        return $this->is_system;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
