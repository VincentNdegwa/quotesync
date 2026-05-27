<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetClientRiskScoreTool implements Tool
{
    public function __construct(
        private readonly ?Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Calculate and return client health/risk scores (0–100) with breakdowns '
            . 'of contributing factors: win rate, average time to close, total won value, and '
            . 'recent activity. For a specific client or multiple clients with optional filtering.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->client) {
            return [
                'recalculate' => $schema->boolean()
                    ->description('If true, recalculate the score fresh from latest data. If false, return cached score.')
                    ->required(),
            ];
        }

        return [
            'recalculate' => $schema->boolean()
                ->description('If true, recalculate scores fresh from latest data. If false, return cached scores.')
                ->required(),
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
            return $this->getSingleClientRiskScore($request);
        }

        return $this->getMultipleClientsRiskScores($request);
    }

    private function getSingleClientRiskScore(Request $request): string
    {
        $client = $this->client;

        if ($request['recalculate'] ?? false) {
            $client->calculateHealthScore();
            $client->refresh();
        }

        $quotes = $client->quotes()->get();
        $total = $quotes->count();
        $won = $quotes->where('status', 'won')->count();
        $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

        $closedQuotes = $quotes->whereIn('status', ['won', 'lost'])
            ->filter(fn ($q) => $q->won_at || $q->lost_at);

        $avgDaysToClose = 0;
        if ($closedQuotes->isNotEmpty()) {
            $avgDaysToClose = round(
                $closedQuotes->avg(fn ($q) => $q->created_at->diffInDays($q->won_at ?? $q->lost_at))
            );
        }

        $totalWonValue = $quotes->where('status', 'won')->sum('total');
        $recentActivity = $quotes->where('created_at', '>=', now()->subDays(90))->count();

        $winRateScore = round(($winRate / 100) * 30, 1);
        $timeToCloseScore = round(max(0, 30 - min($avgDaysToClose, 30)), 1);
        $valueScore = round(min(($totalWonValue / 10000) * 20, 20), 1);
        $recentActivityScore = round(min(($recentActivity / 5) * 20, 20), 1);

        $riskLevel = match (true) {
            $client->health_score >= 70 => 'Low risk — healthy client',
            $client->health_score >= 40 => 'Medium risk — monitor closely',
            default => 'High risk — intervention recommended',
        };

        $client->risk_level = $riskLevel;
        $client->win_rate_pct = $winRate;
        $client->avg_days_to_close = $avgDaysToClose;
        $client->total_won_value = $totalWonValue;
        $client->recent_quotes_90d = $recentActivity;
        $client->score_breakdown = [
            'win_rate_score' => "{$winRateScore}/30",
            'time_to_close_score' => "{$timeToCloseScore}/30",
            'value_score' => "{$valueScore}/20",
            'activity_score' => "{$recentActivityScore}/20",
        ];

        return json_encode($client->toArray(), JSON_PRETTY_PRINT);
    }

    private function getMultipleClientsRiskScores(Request $request): string
    {
        $recalculate = $request['recalculate'] ?? false;
        $limit = $request['limit'] ?? 10;
        $healthScoreMin = $request['health_score_min'] ?? null;
        $healthScoreMax = $request['health_score_max'] ?? null;

        $query = Client::withoutGlobalScopes()->with('quotes')
            ->where('workspace_id', $this->user->current_workspace_id);

        if ($healthScoreMin !== null) {
            $query->where('health_score', '>=', $healthScoreMin);
        }

        if ($healthScoreMax !== null) {
            $query->where('health_score', '<=', $healthScoreMax);
        }

        $clients = $query->limit($limit)->get();

        if ($recalculate) {
            foreach ($clients as $client) {
                $client->calculateHealthScore();
            }
            $clients = $clients->fresh();
        }

        $clients->each(function ($client) {
            $quotes = $client->quotes;
            $total = $quotes->count();
            $won = $quotes->where('status', 'won')->count();
            $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

            $closedQuotes = $quotes->whereIn('status', ['won', 'lost'])
                ->filter(fn ($q) => $q->won_at || $q->lost_at);

            $avgDaysToClose = 0;
            if ($closedQuotes->isNotEmpty()) {
                $avgDaysToClose = round(
                    $closedQuotes->avg(fn ($q) => $q->created_at->diffInDays($q->won_at ?? $q->lost_at))
                );
            }

            $totalWonValue = $quotes->where('status', 'won')->sum('total');
            $recentActivity = $quotes->where('created_at', '>=', now()->subDays(90))->count();

            $riskLevel = match (true) {
                $client->health_score >= 70 => 'Low risk',
                $client->health_score >= 40 => 'Medium risk',
                default => 'High risk',
            };

            $client->risk_level = $riskLevel;
            $client->win_rate_pct = $winRate;
            $client->avg_days_to_close = $avgDaysToClose;
            $client->total_won_value = $totalWonValue;
            $client->recent_quotes_90d = $recentActivity;
        });

        return json_encode([
            'total_returned' => $clients->count(),
            'clients' => $clients->toArray(),
        ], JSON_PRETTY_PRINT);
    }
}
