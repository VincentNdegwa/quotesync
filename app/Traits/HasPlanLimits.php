<?php

namespace App\Traits;

use App\Models\Workspace;
use App\Models\WorkspaceUsage;

trait HasPlanLimits
{
    protected ?Workspace $workspace = null;

    protected ?WorkspaceUsage $currentUsage = null;

    protected array $cachedCounts = [];

    protected function getWorkspace(): ?Workspace
    {
        return $this->workspace ??= request()->attributes->get('workspace');
    }

    protected function getPlanFeatures(): array
    {
        return request()->attributes->get('workspace_plan_features', []);
    }

    protected function getCurrentUsage(): WorkspaceUsage
    {
        return $this->currentUsage ??= $this->getWorkspace()->currentUsage();
    }

    protected function getCount(string $relation): int
    {
        return $this->cachedCounts[$relation] ??= $this->getWorkspace()->{$relation}()->count();
    }

    protected function canCreateQuote(): bool
    {
        $max = $this->getPlanFeatures()['max_quotes_per_month'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCurrentUsage()->quotes_sent < $max;
    }

    protected function canSendInvoice(): bool
    {
        $max = $this->getPlanFeatures()['max_invoices_per_month'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCurrentUsage()->invoices_sent < $max;
    }

    protected function canAddCatalogItem(): bool
    {
        $max = $this->getPlanFeatures()['max_catalog_items'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCount('catalogItems') < $max;
    }

    protected function canAddClient(): bool
    {
        $max = $this->getPlanFeatures()['max_clients'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCount('clients') < $max;
    }

    protected function canAddUser(): bool
    {
        $max = $this->getPlanFeatures()['max_users'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCount('members') < $max;
    }

    protected function canAddTemplate(): bool
    {
        $max = $this->getPlanFeatures()['max_templates'] ?? null;
        if ($max === null) {
            return true;
        }

        return $this->getCount('quoteTemplates') < $max;
    }

    protected function canUseAi(): bool
    {
        $credits = $this->getPlanFeatures()['ai_credits_per_month'] ?? null;
        if ($credits === null) {
            return true;
        }

        return $credits > 0;
    }

    protected function limitExceeded(string $feature)
    {
        $messages = [
            'quotes' => 'You have reached your monthly quote limit. Upgrade to send more.',
            'invoices' => 'You have reached your monthly invoice limit. Upgrade to send more.',
            'catalog_items' => 'You have reached your catalog item limit. Upgrade to add more.',
            'clients' => 'You have reached your client limit. Upgrade to add more.',
            'users' => 'You have reached your user limit. Upgrade to add more team members.',
            'templates' => 'You have reached your template limit. Upgrade to create more.',
            'ai' => 'You have reached your AI credit limit. Upgrade to use AI features.',
        ];

        return redirect()->route('billing.subscribe', ['plan' => 'growth'])
            ->with('error', $messages[$feature] ?? 'You have reached a limit. Upgrade to continue.');
    }
}
