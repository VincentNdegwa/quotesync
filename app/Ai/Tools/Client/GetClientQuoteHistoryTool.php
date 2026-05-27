<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetClientQuoteHistoryTool implements Tool
{
    public function __construct(
        private readonly ?Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Retrieve detailed quote history for a specific client or multiple clients. '
            . 'Filter by status (won, lost, sent, draft, viewed, expired) and limit the results. '
            . 'Use this to analyse patterns, understand why quotes were lost, or check pending quotes.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->client) {
            return [
                'status' => $schema->string()
                    ->enum(['all', 'won', 'lost', 'sent', 'draft', 'viewed', 'expired'])
                    ->description('Filter quotes by status. Use "all" to get everything.')
                    ->required(),
                'limit' => $schema->integer()
                    ->min(1)
                    ->max(50)
                    ->description('Maximum number of quotes to return. Default 10.')
                    ->required(),
            ];
        }

        return [
            'status' => $schema->string()
                ->enum(['all', 'won', 'lost', 'sent', 'draft', 'viewed', 'expired'])
                ->description('Filter quotes by status. Use "all" to get everything.')
                ->required(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of quotes to return. Default 10.')
                ->required(),
            'client_limit' => $schema->integer()
                ->min(1)
                ->max(20)
                ->description('Maximum number of clients to include. Default 5.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->client) {
            return $this->getSingleClientQuoteHistory($request);
        }

        return $this->getMultipleClientsQuoteHistory($request);
    }

    private function getSingleClientQuoteHistory(Request $request): string
    {
        $status = $request['status'] ?? 'all';
        $limit = $request['limit'] ?? 10;

        $query = $this->client->quotes()->withoutGlobalScopes()
            ->with(['lineItems', 'template'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $quotes = $query->limit($limit)->get();

        if ($quotes->isEmpty()) {
            return json_encode(['message' => "No quotes found with status: {$status}"]);
        }

        $quotes->each(function ($q) {
            $q->days_to_close = ($q->accepted_at || $q->declined_at || $q->won_at || $q->lost_at)
                ? $q->created_at->diffInDays($q->accepted_at ?? $q->declined_at ?? $q->won_at ?? $q->lost_at)
                : null;
        });

        return json_encode([
            'total_returned' => $quotes->count(),
            'status_filter' => $status,
            'quotes' => $quotes->toArray(),
        ], JSON_PRETTY_PRINT);
    }

    private function getMultipleClientsQuoteHistory(Request $request): string
    {
        $status = $request['status'] ?? 'all';
        $limit = $request['limit'] ?? 10;
        $clientLimit = $request['client_limit'] ?? 5;

        $query = Client::withoutGlobalScopes()->with(['quotes' => fn ($q) => $q->withoutGlobalScopes()->with(['lineItems', 'template'])->latest()])
            ->where('workspace_id', $this->user->current_workspace_id);

        if ($status !== 'all') {
            $query->whereHas('quotes', fn ($q) => $q->where('status', $status));
        }

        $clients = $query->limit($clientLimit)->get();

        $allQuotes = collect();

        foreach ($clients as $client) {
            $clientQuotes = $client->quotes;

            if ($status !== 'all') {
                $clientQuotes = $clientQuotes->where('status', $status);
            }

            $clientQuotes->take($limit)->each(function ($q) use ($client) {
                $q->client_id = $client->id;
                $q->client_name = $client->company_name;
                $q->days_to_close = ($q->accepted_at || $q->declined_at || $q->won_at || $q->lost_at)
                    ? $q->created_at->diffInDays($q->accepted_at ?? $q->declined_at ?? $q->won_at ?? $q->lost_at)
                    : null;
            });

            $allQuotes = $allQuotes->concat($clientQuotes->take($limit));
        }

        return json_encode([
            'total_clients' => $clients->count(),
            'total_quotes' => $allQuotes->count(),
            'status_filter' => $status,
            'quotes' => $allQuotes->take($limit)->toArray(),
        ], JSON_PRETTY_PRINT);
    }
}
