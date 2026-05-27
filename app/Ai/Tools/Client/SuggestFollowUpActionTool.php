<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestFollowUpActionTool implements Tool
{
    public function __construct(
        private readonly ?Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyse client state and recommend the best next action '
            . '— follow up on stale quotes, resolve overdue invoices, schedule check-ins, or re-engage. '
            . 'For a specific client or multiple clients needing attention.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->client) {
            return [
                'context' => $schema->string()
                    ->description('Optional extra context from the user.')
                    ->nullable(),
            ];
        }

        return [
            'limit' => $schema->integer()
                ->min(1)
                ->max(20)
                ->description('Maximum number of clients to return. Default 5.')
                ->required(),
            'urgency' => $schema->string()
                ->enum(['all', 'high', 'medium', 'low'])
                ->description('Filter by urgency level.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->client) {
            return $this->getSingleClientSuggestion($request);
        }

        return $this->getMultipleClientsSuggestions($request);
    }

    private function getSingleClientSuggestion(Request $request): string
    {
        $client = $this->client->load(['quotes', 'contacts']);
        $userContext = $request['context'] ?? '';

        $quotes = $client->quotes;
        $overdue = Invoice::where('client_id', $client->id)
            ->where('status', 'overdue')->count();

        $staleQuote = $quotes
            ->where('status', 'sent')
            ->filter(fn ($q) => $q->sent_at && $q->sent_at->diffInDays(now()) >= 5)
            ->sortByDesc('sent_at')
            ->first();

        $viewedQuote = $quotes
            ->where('status', 'viewed')
            ->sortByDesc('viewed_at')
            ->first();

        $lastActivity = $quotes->max('created_at');
        $daysSinceActivity = $lastActivity ? now()->diffInDays($lastActivity) : 999;

        $suggestion = match (true) {
            $overdue > 0 => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'Resolve overdue invoice(s)',
                'urgency' => 'high',
                'reason' => "This client has {$overdue} overdue invoice(s). Avoid sending new quotes until resolved.",
                'suggested_message' => "Hi {$client->contact_name}, I wanted to follow up on your outstanding invoice(s). Could we arrange payment or discuss a payment plan?",
            ],
            $viewedQuote !== null => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'Follow up on viewed quote',
                'urgency' => 'high',
                'reason' => "Quote #{$viewedQuote->number} was viewed but not signed. High intent — reach out now.",
                'suggested_message' => "Hi {$client->contact_name}, I saw you had a chance to look at our quote. Do you have any questions or would you like to adjust anything before moving forward?",
                'quote_id' => $viewedQuote->id,
            ],
            $staleQuote !== null => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'Chase stale quote',
                'urgency' => 'medium',
                'reason' => "Quote #{$staleQuote->number} was sent {$staleQuote->sent_at->diffForHumans()} with no response.",
                'suggested_message' => "Hi {$client->contact_name}, just checking in on the quote we sent over. Happy to answer any questions or adjust the scope.",
                'quote_id' => $staleQuote->id,
            ],
            $client->health_score < 40 => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'Re-engage the client',
                'urgency' => 'medium',
                'reason' => "Health score is {$client->health_score}/100. Low win rate and/or low recent activity.",
                'suggested_message' => "Hi {$client->contact_name}, it's been a while since we last worked together. I'd love to catch up and see if there's anything we can help with.",
            ],
            $daysSinceActivity > 60 => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'Schedule a check-in',
                'urgency' => 'low',
                'reason' => "No quote activity in {$daysSinceActivity} days. Keep the relationship warm.",
                'suggested_message' => "Hi {$client->contact_name}, hope things are going well! We have some new services and pricing that might be relevant to you — would love 15 minutes to connect.",
            ],
            default => [
                'client_id' => $client->id,
                'client_name' => $client->company_name,
                'action' => 'No urgent action needed',
                'urgency' => 'none',
                'reason' => 'Client account is in good standing. Continue monitoring.',
                'suggested_message' => null,
            ],
        };

        if ($userContext) {
            $suggestion['user_context_noted'] = $userContext;
        }

        return json_encode($suggestion, JSON_PRETTY_PRINT);
    }

    private function getMultipleClientsSuggestions(Request $request): string
    {
        $limit = $request['limit'] ?? 5;
        $urgencyFilter = $request['urgency'] ?? 'all';

        $clients = Client::withoutGlobalScopes()->with(['quotes', 'contacts'])
            ->where('workspace_id', $this->user->current_workspace_id)
            ->get();

        $suggestions = collect();

        foreach ($clients as $client) {
            $quotes = $client->quotes;
            $overdue = Invoice::where('client_id', $client->id)
                ->where('status', 'overdue')->count();

            $staleQuote = $quotes
                ->where('status', 'sent')
                ->filter(fn ($q) => $q->sent_at && $q->sent_at->diffInDays(now()) >= 5)
                ->sortByDesc('sent_at')
                ->first();

            $viewedQuote = $quotes
                ->where('status', 'viewed')
                ->sortByDesc('viewed_at')
                ->first();

            $lastActivity = $quotes->max('created_at');
            $daysSinceActivity = $lastActivity ? now()->diffInDays($lastActivity) : 999;

            $suggestion = match (true) {
                $overdue > 0 => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'action' => 'Resolve overdue invoice(s)',
                    'urgency' => 'high',
                    'reason' => "{$overdue} overdue invoice(s).",
                ],
                $viewedQuote !== null => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'action' => 'Follow up on viewed quote',
                    'urgency' => 'high',
                    'reason' => "Quote #{$viewedQuote->number} viewed but not signed.",
                ],
                $staleQuote !== null => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'action' => 'Chase stale quote',
                    'urgency' => 'medium',
                    'reason' => "Quote sent {$staleQuote->sent_at->diffForHumans()} with no response.",
                ],
                $client->health_score < 40 => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'action' => 'Re-engage the client',
                    'urgency' => 'medium',
                    'reason' => "Health score {$client->health_score}/100.",
                ],
                $daysSinceActivity > 60 => [
                    'client_id' => $client->id,
                    'client_name' => $client->company_name,
                    'action' => 'Schedule a check-in',
                    'urgency' => 'low',
                    'reason' => "No activity in {$daysSinceActivity} days.",
                ],
                default => null,
            };

            if ($suggestion) {
                if ($urgencyFilter === 'all' || $suggestion['urgency'] === $urgencyFilter) {
                    $suggestions->push($suggestion);
                }
            }
        }

        $sorted = $suggestions->sortByDesc(fn ($s) => match ($s['urgency']) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        });

        return json_encode([
            'total_returned' => $sorted->count(),
            'suggestions' => $sorted->take($limit)->values(),
        ], JSON_PRETTY_PRINT);
    }
}
