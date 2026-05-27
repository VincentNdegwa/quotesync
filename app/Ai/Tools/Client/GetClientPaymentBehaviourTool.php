<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetClientPaymentBehaviourTool implements Tool
{
    public function __construct(
        private readonly ?Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyse client invoice payment history. Returns average days to pay, '
            . 'number of overdue invoices, late payment rate, and payment profile. '
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
            'has_overdue' => $schema->boolean()
                ->description('Filter to only clients with overdue invoices.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->client) {
            return $this->getSingleClientPaymentBehaviour();
        }

        return $this->getMultipleClientsPaymentBehaviour($request);
    }

    private function getSingleClientPaymentBehaviour(): string
    {
        $invoices = Invoice::where('client_id', $this->client->id)
            ->get();

        if ($invoices->isEmpty()) {
            return json_encode([
                'message' => 'No invoice history found for this client.',
                'payment_profile' => 'Unknown — no invoices yet.',
            ]);
        }

        $paid = $invoices->where('status', 'paid');
        $overdue = $invoices->where('status', 'overdue');
        $pending = $invoices->whereIn('status', ['sent', 'viewed']);
        $totalOwed = $pending->sum('total') + $overdue->sum('total');

        $avgDaysToPay = 0;
        $payablePaid = $paid->filter(fn ($i) => $i->paid_date && $i->issue_date);
        if ($payablePaid->isNotEmpty()) {
            $avgDaysToPay = round(
                $payablePaid->avg(fn ($i) => $i->issue_date->diffInDays($i->paid_date))
            );
        }

        $latePaid = $paid->filter(fn ($i) => $i->paid_date && $i->due_date && $i->paid_date->gt($i->due_date));
        $lateRate = $paid->count() > 0 ? round(($latePaid->count() / $paid->count()) * 100, 1) : 0;

        $profile = match (true) {
            $overdue->count() > 2 => 'High risk payer — multiple overdue invoices.',
            $lateRate > 50 => 'Habitual late payer — consistently pays after due date.',
            $lateRate > 20 => 'Occasionally late — worth monitoring.',
            $avgDaysToPay <= 15 => 'Excellent payer — pays quickly.',
            default => 'Reliable payer — no major concerns.',
        };

        $this->client->payment_profile = $profile;
        $this->client->total_invoices = $invoices->count();
        $this->client->paid_invoices = $paid->count();
        $this->client->overdue_invoices = $overdue->count();
        $this->client->pending_invoices = $pending->count();
        $this->client->total_currently_owed = $totalOwed;
        $this->client->avg_days_to_pay = $avgDaysToPay;
        $this->client->late_payment_rate = "{$lateRate}%";
        $this->client->recommendation = $overdue->count() > 0
            ? 'Resolve overdue invoices before issuing new quotes.'
            : ($lateRate > 50 ? 'Consider requiring a 50% deposit on future quotes.' : null);

        return json_encode($this->client->toArray(), JSON_PRETTY_PRINT);
    }

    private function getMultipleClientsPaymentBehaviour(Request $request): string
    {
        $limit = $request['limit'] ?? 10;
        $hasOverdue = $request['has_overdue'] ?? null;

        $query = Client::withoutGlobalScopes()->with('invoices')
            ->where('workspace_id', $this->user->current_workspace_id);

        if ($hasOverdue === true) {
            $query->whereHas('invoices', fn ($q) => $q->where('status', 'overdue'));
        }

        $clients = $query->limit($limit)->get();

        $clients->each(function ($client) {
            $invoices = $client->invoices;

            if ($invoices->isEmpty()) {
                $client->payment_profile = 'Unknown — no invoices yet.';
                $client->total_invoices = 0;
                return;
            }

            $paid = $invoices->where('status', 'paid');
            $overdue = $invoices->where('status', 'overdue');
            $pending = $invoices->whereIn('status', ['sent', 'viewed']);
            $totalOwed = $pending->sum('total') + $overdue->sum('total');

            $avgDaysToPay = 0;
            $payablePaid = $paid->filter(fn ($i) => $i->paid_date && $i->issue_date);
            if ($payablePaid->isNotEmpty()) {
                $avgDaysToPay = round(
                    $payablePaid->avg(fn ($i) => $i->issue_date->diffInDays($i->paid_date))
                );
            }

            $latePaid = $paid->filter(fn ($i) => $i->paid_date && $i->due_date && $i->paid_date->gt($i->due_date));
            $lateRate = $paid->count() > 0 ? round(($latePaid->count() / $paid->count()) * 100, 1) : 0;

            $profile = match (true) {
                $overdue->count() > 2 => 'High risk',
                $lateRate > 50 => 'Habitual late',
                $lateRate > 20 => 'Occasionally late',
                $avgDaysToPay <= 15 => 'Excellent',
                default => 'Reliable',
            };

            $client->payment_profile = $profile;
            $client->total_invoices = $invoices->count();
            $client->overdue_invoices = $overdue->count();
            $client->total_currently_owed = $totalOwed;
            $client->avg_days_to_pay = $avgDaysToPay;
            $client->late_payment_rate = "{$lateRate}%";
        });

        return json_encode([
            'total_returned' => $clients->count(),
            'clients' => $clients->toArray(),
        ], JSON_PRETTY_PRINT);
    }
}
