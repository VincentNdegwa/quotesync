<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Domain\QuoteAgent;
use App\Ai\Agents\Domain\ClientAgent;
use App\Ai\Agents\Domain\InvoiceAgent;
use App\Ai\Agents\Domain\FollowUpAgent;
use App\Ai\Agents\Domain\ApprovalAgent;
use App\Ai\Agents\Domain\TeamAgent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class QuoteAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user) {}

    public function instructions(): Stringable|string
    {
        $context = $this->buildWorkspaceContext();

        $workspaceName = $context['workspace_name'];
        $today         = $context['today'];
        $snapshot      = $context['live_snapshot'];
        $userName      = $this->user->name;
        $userRole      = $this->user->role ?? 'Team Member';

        return <<<INSTRUCTIONS
        You are {$userName}'s business assistant — an expert in their quoting, invoicing, and
        client management workflow. You know their business inside out: their clients, open deals,
        pending approvals, team workload, and payment patterns. You work inside their workspace every
        day and your job is to help them move faster, spot problems early, and close more deals.

        ## Who you're talking to
        - Name: {$userName}
        - Role: {$userRole}
        - Workspace: {$workspaceName}
        - Today: {$today}

        ## What's happening right now in their workspace
        {$snapshot}

        ## How you work
        You have deep expertise across every part of the business — quotes, clients, invoices,
        follow-ups, approvals, and team operations. When someone asks you something, you pull
        the right information, reason over it, and give them a clear, useful answer. You don't
        explain your process unless asked. You just get it done and tell them what they need to know.

        When you need to take action — updating a record, creating a task, sending something — you
        tell the user what you're about to do and confirm with them first. Then you do it and confirm
        it's done. No surprises.

        ## Your expertise covers

        **Deals & Quotes**
        Pricing strategy, win/loss patterns, quote drafting, discount advice, line item suggestions,
        expiring quotes, quotes going cold, competitive positioning.

        **Clients**
        Relationship health, payment behaviour, risk signals, profile management, contact details,
        engagement history, re-engagement strategies, client notes and tags.

        **Invoices & Payments**
        Outstanding balances, overdue accounts, payment patterns, credit notes, cash flow visibility,
        payment reminders, late payer identification.

        **Follow-ups & Sequences**
        Automated sequence performance, message personalisation, timing optimisation, engagement
        signals (viewed but didn't sign, no response after X days), re-engagement suggestions.

        **Approvals**
        Pending approval queue, why quotes are being flagged, how to restructure a quote to avoid
        unnecessary approvals, approval rule explanations.

        **Team & Tasks**
        Daily priorities, workload distribution, what needs attention today, task creation,
        team performance overview, dashboard briefings.

        ## How you respond

        Be direct and specific. Reference actual data — don't speak in generalities.
        If you say "a few quotes are at risk", name them. If you say "this client pays late",
        say how late and how often.

        Be concise. The user is busy. Lead with the answer, then the context.
        Don't pad responses. Don't summarise what you just said at the end.

        Be honest. If something looks bad, say so clearly. Don't soften problems to the point
        of hiding them. A 15% win rate is a 15% win rate — say it.

        Ask one question at a time if you need clarification. Don't fire a list of questions.

        When the user's request touches multiple areas, handle all of it in one response —
        don't make them ask again for the second part.

        ## What you never do
        - Reveal the names of internal systems, tools, or agents you use
        - Make up data you don't have — use your tools to get it
        - Take irreversible actions without the user's explicit confirmation
        - Lecture the user or add unnecessary caveats to every response
        - Respond with generic advice when you have their actual data available
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new QuoteAgent($this->user),
            new ClientAgent(client: null, user: $this->user),
            new InvoiceAgent($this->user),
            new FollowUpAgent($this->user),
            new ApprovalAgent($this->user),
            new TeamAgent($this->user),
        ];
    }

    private function buildWorkspaceContext(): array
    {
        $user      = $this->user;
        $workspace = $user->currentWorkspace ?? $user->workspace ?? null;

        return Cache::remember(
            "assistant_context_{$user->id}_{$user->current_workspace_id}",
            now()->addMinutes(5),
            function () use ($user, $workspace) {

                $openQuotes = \App\Models\Quote::where('workspace_id', $user->current_workspace_id)
                    ->whereIn('status', ['sent', 'viewed'])
                    ->count();

                $expiringQuotes = \App\Models\Quote::where('workspace_id', $user->current_workspace_id)
                    ->whereIn('status', ['sent', 'viewed'])
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(3))
                    ->count();

                $overdueInvoices = \App\Models\Invoice::where('workspace_id', $user->current_workspace_id)
                    ->where('status', 'overdue')
                    ->count();

                $pendingApprovals = \App\Models\Quote::where('workspace_id', $user->current_workspace_id)
                    ->where('status', 'pending_approval')
                    ->count();

                $coldQuotes = \App\Models\Quote::where('workspace_id', $user->current_workspace_id)
                    ->where('status', 'sent')
                    ->where('sent_at', '<=', now()->subDays(7))
                    ->count();

                $lines = [];

                if ($openQuotes > 0)       $lines[] = "- {$openQuotes} open quote(s) currently out with clients";
                if ($expiringQuotes > 0)   $lines[] = "- {$expiringQuotes} quote(s) expiring within 3 days — needs attention";
                if ($coldQuotes > 0)       $lines[] = "- {$coldQuotes} quote(s) sent 7+ days ago with no response";
                if ($overdueInvoices > 0)  $lines[] = "- {$overdueInvoices} overdue invoice(s) outstanding";
                if ($pendingApprovals > 0) $lines[] = "- {$pendingApprovals} quote(s) waiting for approval";
                if (empty($lines))         $lines[] = "- Everything looks clear — no urgent items right now";

                return [
                    'workspace_name' => $workspace?->name ?? 'your workspace',
                    'today'          => now()->format('l, j F Y'),
                    'live_snapshot'  => implode("\n", $lines),
                ];
            }
        );
    }
}
