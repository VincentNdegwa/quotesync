<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ConfigurationUnit;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class BuilderLookupService
{
    /**
     * Get catalog items for builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCatalogItems(Workspace $workspace)
    {
        return CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->with(['taxes', 'configurationUnit'])
            ->orderByRaw('LOWER(name)')
            ->limit(300)
            ->get();
    }

    /**
     * Get taxes for builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTaxes(Workspace $workspace)
    {
        return Tax::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByRaw('LOWER(name)')
            ->get();
    }

    /**
     * Get configuration units for builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnits(Workspace $workspace)
    {
        return ConfigurationUnit::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByRaw('LOWER(name)')
            ->get();
    }

    /**
     * Get clients for quote builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClients(Workspace $workspace)
    {
        return Client::query()
            ->where('workspace_id', $workspace->id)
            ->orderByRaw('LOWER(company_name)')
            ->get();
    }

    /**
     * Get quote templates for builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getQuoteTemplates(Workspace $workspace)
    {
        return QuoteTemplate::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByDesc('is_system')
            ->orderByRaw('LOWER(name)')
            ->get();
    }

    /**
     * Get basic builder lookups (catalog items, taxes, units)
     * Used for invoices and templates
     *
     * @return array<string, mixed>
     */
    public function getBasicLookups(Workspace $workspace): array
    {
        return [
            'catalogItems' => $this->getCatalogItems($workspace),
            'taxes' => $this->getTaxes($workspace),
            'units' => $this->getUnits($workspace),
        ];
    }

    /**
     * Get full builder lookups (includes clients and templates)
     * Used for quotes
     *
     * @return array<string, mixed>
     */
    public function getFullLookups(Workspace $workspace): array
    {
        return [
            'clients' => $this->getClients($workspace),
            'catalogItems' => $this->getCatalogItems($workspace),
            'templates' => $this->getQuoteTemplates($workspace),
            'taxes' => $this->getTaxes($workspace),
            'units' => $this->getUnits($workspace),
        ];
    }

    /**
     * Get template builder lookups (includes branding)
     * Used for quote templates
     *
     * @return array<string, mixed>
     */
    public function getTemplateLookups(Workspace $workspace, WorkspaceSettingsService $workspaceSettingsService): array
    {
        $branding = $this->getBrandingPayload($workspace, $workspaceSettingsService);

        return [
            'branding' => $branding,
            'catalogItems' => $this->getCatalogItems($workspace),
            'taxes' => $this->getTaxes($workspace),
        ];
    }

    /**
     * Get branding payload for templates
     *
     * @return array<string, mixed>
     */
    private function getBrandingPayload(Workspace $workspace, WorkspaceSettingsService $workspaceSettingsService): array
    {
        $branding = $workspaceSettingsService->groupForFrontend($workspace, 'branding')['fields'] ?? [];

        return [
            'company_name' => $branding['company_name']['value'] ?? null,
            'logo_url' => $branding['logo_url']['value'] ?? null,
            'primary_color' => $branding['primary_color']['value'] ?? '#2563EB',
            'accent_color' => $branding['accent_color']['value'] ?? '#F59E0B',
            'company_email' => $branding['company_email']['value'] ?? null,
            'company_phone' => $branding['company_phone']['value'] ?? null,
            'company_address' => $branding['company_address']['value'] ?? null,
            'company_tagline' => $branding['company_tagline']['value'] ?? null,
        ];
    }
}
