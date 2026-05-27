<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetClientInsightsTool implements Tool
{
    public function __construct(
        private readonly ?Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Retrieve comprehensive client data including quotes, contacts, tags, notes. '
            . 'For a specific client or multiple clients with optional filtering.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->client) {
            return [];
        }

        return [
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of clients to return. Default 10.')
                ->required(),
            'health_score_min' => $schema->integer()
                ->min(0)
                ->max(100)
                ->description('Filter by minimum health score.')
                ->nullable(),
            'health_score_max' => $schema->integer()
                ->min(0)
                ->max(100)
                ->description('Filter by maximum health score.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->client) {
            return $this->getSingleClientInsights();
        }

        return $this->getMultipleClientsInsights($request);
    }

    private function getSingleClientInsights(): string
    {
        $client = $this->client->load([
            'quotes' => fn ($q) => $q->withoutGlobalScopes(),
            'contacts',
            'tags',
            'notes' => fn ($q) => $q->latest()->limit(5),
            'primaryContact',
        ]);

        return json_encode($client->toArray(), JSON_PRETTY_PRINT);
    }

    private function getMultipleClientsInsights(Request $request): string
    {
        $limit = $request['limit'] ?? 10;
        $healthScoreMin = $request['health_score_min'] ?? null;
        $healthScoreMax = $request['health_score_max'] ?? null;

        $query = Client::withoutGlobalScopes()->with([
            'quotes',
            'contacts',
            'tags',
            'notes' => fn ($q) => $q->latest()->limit(5),
            'primaryContact',
        ])->where('workspace_id', $this->user->current_workspace_id);

        if ($healthScoreMin !== null) {
            $query->where('health_score', '>=', $healthScoreMin);
        }

        if ($healthScoreMax !== null) {
            $query->where('health_score', '<=', $healthScoreMax);
        }

        $clients = $query->limit($limit)->get();

        return json_encode([
            'total_returned' => $clients->count(),
            'clients' => $clients->toArray(),
        ], JSON_PRETTY_PRINT);
    }
}
