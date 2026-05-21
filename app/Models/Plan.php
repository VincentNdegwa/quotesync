<?php

namespace App\Models;

use App\Services\WorkspacePlanCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::updated(function (Plan $plan) {
            if ($plan->isDirty('features')) {
                // Invalidate cache for workspaces with this plan
                $workspaces = \App\Models\Workspace::where('plan_id', $plan->id)->get();
                foreach ($workspaces as $workspace) {
                    app(WorkspacePlanCache::class)->invalidate($workspace);
                }
            }
        });

        static::deleted(function (Plan $plan) {
            // Invalidate cache for workspaces with this plan
            $workspaces = \App\Models\Workspace::where('plan_id', $plan->id)->get();
            foreach ($workspaces as $workspace) {
                app(WorkspacePlanCache::class)->invalidate($workspace);
            }
        });
    }

    protected $fillable = [
        'slug',
        'name',
        'description',
        'monthly_price',
        'yearly_price',
        'paddle_monthly_price_id',
        'paddle_yearly_price_id',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFeature(string $key, mixed $default = null): mixed
    {
        return $this->features[$key] ?? $default;
    }
}
