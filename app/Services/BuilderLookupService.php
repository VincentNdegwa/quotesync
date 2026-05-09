<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ConfigurationUnit;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\Workspace;

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
            ->with(['taxes', 'configurationUnit', 'variants', 'priceTiers'])
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
     * Get template builder lookups
     * Used for quote templates
     *
     * @return array<string, mixed>
     */
    public function getTemplateLookups(Workspace $workspace): array
    {
        return [
            'catalogItems' => $this->getCatalogItems($workspace),
            'taxes' => $this->getTaxes($workspace),
        ];
    }
}
