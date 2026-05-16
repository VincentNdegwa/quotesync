<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class WorkspacePlanCache
{
    private const CACHE_PREFIX = 'workspace_plan:';
    private const CACHE_TTL = 86400;

    public function getPlan(Workspace $workspace): ?Plan
    {
        $cacheKey = $this->getCacheKey($workspace->id, 'plan');
        $cached = Cache::get($cacheKey);

        if ($cached instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $cached = null;
        }

        if ($cached === null) {
            $cached = $workspace->plan;
            Cache::put($cacheKey, $cached, self::CACHE_TTL);
        }

        return $cached;
    }

    public function getPlanFeatures(Workspace $workspace): array
    {
        $cacheKey = $this->getCacheKey($workspace->id, 'features');
        $cached = Cache::get($cacheKey);

        if ($cached instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $cached = null;
        }

        if ($cached === null) {
            $cached = $workspace->plan ? $workspace->plan->features : [];
            Cache::put($cacheKey, $cached, self::CACHE_TTL);
        }

        return $cached;
    }

    public function getPlanFeature(Workspace $workspace, string $key, mixed $default = null): mixed
    {
        $features = $this->getPlanFeatures($workspace);
        return $features[$key] ?? $default;
    }

    public function canUseFeature(Workspace $workspace, string $feature): bool
    {
        $value = $this->getPlanFeature($workspace, $feature);

        if (is_bool($value)) {
            return $value;
        }

        return $value !== null && $value !== 0;
    }

    public function invalidate(Workspace $workspace): void
    {
        Cache::forget($this->getCacheKey($workspace->id, 'plan'));
        Cache::forget($this->getCacheKey($workspace->id, 'features'));
    }

    public function invalidateAll(): void
    {
        Cache::forgetMatching($this->getCacheKey('*', '*'));
    }

    private function getCacheKey(int $workspaceId, string $type): string
    {
        return self::CACHE_PREFIX.$workspaceId.':'.$type;
    }

    public function getSubscriptionStatus(Workspace $workspace): array
    {
        $plan = $this->getPlan($workspace);
        $subscription = $workspace->subscription('default');

        return [
            'plan' => $plan,
            'plan_slug' => $plan?->slug,
            'subscription' => $subscription,
            'is_active' => $subscription ? $subscription->active() : false,
            'is_on_trial' => $subscription ? $subscription->onTrial() : false,
            'is_on_grace_period' => $subscription ? $subscription->onGracePeriod() : false,
            'is_cancelled' => $subscription ? $subscription->cancelled() : false,
            'ends_at' => $subscription?->ends_at,
        ];
    }
}
