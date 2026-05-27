<?php

namespace App\Services;

use App\Enums\Feature;
use App\Models\Workspace;
use Illuminate\Support\Facades\Lang;

class UsageLimitService
{
    public function hasReachedLimit(Workspace $workspace, Feature $feature): bool
    {
        $limit = $workspace->plan?->features[$feature->value] ?? null;

        if ($limit === null || $limit === false) {
            return false;
        }

        $currentUsage = $this->getCurrentUsage($workspace, $feature);

        return $currentUsage >= $limit;
    }

    public function canPerformOperation(Workspace $workspace, Feature $feature): bool
    {
        return ! $this->hasReachedLimit($workspace, $feature);
    }

    public function getCurrentUsage(Workspace $workspace, Feature $feature): int
    {
        $workspace->loadCount(['members', 'catalogItems', 'templates', 'clients', 'followUpSequences']);
        $workspace->load(['owner.workspaces', 'usage']);

        return match ($feature) {
            Feature::MAX_USERS => $workspace->members_count,
            Feature::MAX_QUOTES_PER_MONTH => $workspace->usage->quotes_sent ?? 0,
            Feature::MAX_INVOICES_PER_MONTH => $workspace->usage->invoices_sent ?? 0,
            Feature::MAX_CATALOG_ITEMS => $workspace->catalog_items_count,
            Feature::MAX_TEMPLATES => $workspace->templates_count,
            Feature::MAX_CLIENTS => $workspace->clients_count,
            Feature::AI_CREDITS_PER_MONTH => $workspace->usage->ai_credits_used ?? 0,
            Feature::FOLLOW_UP_SEQUENCES => $workspace->follow_up_sequences_count,
            Feature::WORKSPACES => $workspace->owner->workspaces_count,
            default => 0,
        };
    }

    public function getLimit(Workspace $workspace, Feature $feature): ?int
    {
        $limit = $workspace->plan?->features[$feature->value] ?? null;

        if ($limit === null || $limit === false) {
            return null;
        }

        return (int) $limit;
    }

    public function getUsagePercentage(Workspace $workspace, Feature $feature): ?float
    {
        $limit = $this->getLimit($workspace, $feature);

        if ($limit === null) {
            return null;
        }

        $currentUsage = $this->getCurrentUsage($workspace, $feature);

        if ($limit === 0) {
            return 100;
        }

        return min(($currentUsage / $limit) * 100, 100);
    }

    public function getLimitReachedMessage(Feature $feature): string
    {
        return Lang::get('You have reached your :feature limit. Please upgrade your plan.', [
            'feature' => $feature->label(),
        ]);
    }
}
