<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetQuotesTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Retrieve comprehensive quote data including line items, client, status history. '
            . 'For a specific quote or multiple quotes with optional filtering.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'status' => $schema->string()
                ->enum('all', 'draft', 'sent', 'viewed', 'won', 'lost', 'expired', 'pending_approval')
                ->description('Filter by quote status.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of quotes to return. Default 20.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Filter to one client.')
                ->nullable(),
            'date_from' => $schema->string()
                ->description('ISO date format.')
                ->nullable(),
            'date_to' => $schema->string()
                ->description('ISO date format.')
                ->nullable(),
            'min_total' => $schema->number()
                ->description('Minimum total value.')
                ->nullable(),
            'max_total' => $schema->number()
                ->description('Maximum total value.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->quote) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $quote = $this->quote->load(['client', 'lineItems', 'template']);

        $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
        $lineItemsCount = $quote->lineItems->count();
        $expires = $quote->valid_until ? $quote->valid_until->toFormattedDateString() : 'Not set';
        $template = $quote->template ? $quote->template->name : 'Default';
        $notes = $quote->notes ?: 'None';

        return <<<OUTPUT
Quote #{$quote->number} (ID: {$quote->id})
Title: {$quote->title}
Client: {$clientName}
Status: {$quote->status->value}
Total: {$quote->total} {$quote->currency}
Currency: {$quote->currency}
Line Items: {$lineItemsCount}
Created: {$quote->created_at->toFormattedDateString()}
Expires: {$expires}
Template: {$template}
Notes: {$notes}
OUTPUT;
    }

    private function handleWorkspace(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $query = Quote::where('workspace_id', $workspaceId);

        if (!empty($request['status']) && $request['status'] !== 'all') {
            $query->where('status', $request['status']);
        }

        if (!empty($request['client_id'])) {
            $query->where('client_id', $request['client_id']);
        }

        if (!empty($request['date_from'])) {
            $query->where('created_at', '>=', $request['date_from']);
        }

        if (!empty($request['date_to'])) {
            $query->where('created_at', '<=', $request['date_to']);
        }

        if (!empty($request['min_total'])) {
            $query->where('total', '>=', $request['min_total']);
        }

        if (!empty($request['max_total'])) {
            $query->where('total', '<=', $request['max_total']);
        }

        $limit = $request['limit'] ?? 20;
        $quotes = $query->with('client')->limit($limit)->get();

        if ($quotes->isEmpty()) {
            return 'No quotes found matching the criteria.';
        }

        $output = "Found {$quotes->count()} quote(s):\n\n";
        foreach ($quotes as $quote) {
            $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
            $output .= "- ID: {$quote->id}, Number: {$quote->number}, Client: {$clientName}, Status: {$quote->status->value}, Total: {$quote->total} {$quote->currency}\n";
        }

        return $output;
    }
}
