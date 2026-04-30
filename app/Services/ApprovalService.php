<?php

namespace App\Services;

use App\Enums\QuoteActivityType;
use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\QuoteApproval;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\QuoteApprovalApprovedNotification;
use App\Notifications\QuoteApprovalGrantedNotification;
use App\Notifications\QuoteApprovalRejectedNotification;
use App\Notifications\QuoteApprovalRequestedNotification;
use App\Services\Quotes\QuoteSendingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApprovalService
{
    public function __construct(
        private QuoteSendingService $quoteSendingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function index(Workspace $workspace, User $user): array
    {
        $isWorkspaceOwner = $workspace->owner_id === $user->id;

        $pendingApprovalsQuery = QuoteApproval::query()
            ->where('status', QuoteApprovalStatus::Pending->value)
            ->whereHas('quote', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->with([
                'quote' => fn ($query) => $query->select([
                    'id',
                    'workspace_id',
                    'number',
                    'title',
                    'total',
                    'base_total',
                    'currency',
                    'base_currency',
                    'client_id',
                    'created_by',
                ]),
                'quote.client:id,company_name',
                'quote.creator:id,name',
                'approvalRule:id,trigger_type,threshold_value',
            ])
            ->orderByDesc('created_at');

        if ($workspace->owner_id !== $user->id) {
            $pendingApprovalsQuery->where('approver_id', $user->id);
        }

        $workspaceCurrency = $workspace->currency ?? 'USD';

        $pendingApprovals = $pendingApprovalsQuery
            ->get()
            ->map(function (QuoteApproval $approval) use ($workspaceCurrency): array {
                $quote = $approval->quote;

                return [
                    'id' => $approval->id,
                    'created_at' => $approval->created_at?->toIso8601String(),
                    'quote' => [
                        'id' => $quote->id,
                        'number' => $quote->number,
                        'title' => $quote->title,
                        'total' => (float) $quote->base_total,
                        'currency' => $quote->base_currency ?? $workspaceCurrency,
                        'client' => $quote->client ? [
                            'id' => $quote->client->id,
                            'company_name' => $quote->client->company_name,
                        ] : null,
                        'created_by_name' => $quote->creator?->name,
                    ],
                    'approval_rule' => $approval->approvalRule ? [
                        'id' => $approval->approvalRule->id,
                        'trigger_type' => $approval->approvalRule->trigger_type,
                        'threshold_value' => $approval->approvalRule->threshold_value !== null
                            ? (float) $approval->approvalRule->threshold_value
                            : null,
                    ] : null,
                ];
            })
            ->values();

        $rulesQuery = ApprovalRule::query()
            ->where('workspace_id', $workspace->id)
            ->with(['client:id,company_name', 'approver:id,name'])
            ->orderBy('id');

        if ($workspace->owner_id !== $user->id) {
            $rulesQuery->where('approver_id', $user->id);
        }

        $rules = $rulesQuery
            ->get()
            ->map(function (ApprovalRule $rule): array {
                return [
                    'id' => $rule->id,
                    'trigger_type' => $rule->trigger_type,
                    'threshold_value' => $rule->threshold_value !== null ? (float) $rule->threshold_value : null,
                    'client_id' => $rule->client_id,
                    'client' => $rule->client ? [
                        'id' => $rule->client->id,
                        'company_name' => $rule->client->company_name,
                    ] : null,
                    'approver_id' => $rule->approver_id,
                    'approver' => $rule->approver ? [
                        'id' => $rule->approver->id,
                        'name' => $rule->approver->name,
                    ] : null,
                    'is_active' => (bool) $rule->is_active,
                ];
            })
            ->values();

        $approvers = $workspace->members()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->when($workspace->owner, function ($collection) use ($workspace) {
                $collection->push($workspace->owner);

                return $collection;
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($approver) => ['id' => $approver->id, 'name' => $approver->name]);

        $clients = Client::query()
            ->where('workspace_id', $workspace->id)
            ->select('id', 'company_name')
            ->orderBy('company_name')
            ->get()
            ->map(fn ($client) => ['id' => $client->id, 'company_name' => $client->company_name]);

        return [
            'pendingApprovals' => $pendingApprovals,
            'rules' => $rules,
            'approvers' => $approvers,
            'clients' => $clients,
            'currency' => $workspaceCurrency,
        ];
    }

    public function count(Workspace $workspace, User $user): int
    {
        $query = QuoteApproval::query()
            ->where('status', QuoteApprovalStatus::Pending->value)
            ->whereHas('quote', fn ($query) => $query->where('workspace_id', $workspace->id));

        if ($workspace->owner_id !== $user->id) {
            $query->where('approver_id', $user->id);
        }

        return $query->count();
    }

    public function checkApprovalRequired(Quote $quote): bool
    {
        $matchingRules = ApprovalRule::where('workspace_id', $quote->workspace_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($rule) => $rule->matches($quote));

        return $matchingRules->isNotEmpty();
    }

    public function initiateApproval(Quote $quote, int $requestedBy): void
    {
        $matchingRules = ApprovalRule::where('workspace_id', $quote->workspace_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($rule) => $rule->matches($quote));

        if ($matchingRules->isEmpty()) {
            return;
        }

        $approvalsToNotify = [];

        DB::transaction(function () use ($quote, $matchingRules, $requestedBy, &$approvalsToNotify): void {
            $approvalIds = [];

            foreach ($matchingRules as $rule) {
                $approval = QuoteApproval::create([
                    'quote_id' => $quote->id,
                    'approval_rule_id' => $rule->id,
                    'approver_id' => $rule->approver_id,
                    'status' => QuoteApprovalStatus::Pending->value,
                ]);
                $approvalIds[] = $approval->id;
                $approvalsToNotify[] = $approval->load('approver');
            }

            $quote->update([
                'status' => QuoteStatus::PendingApproval->value,
                'approval_granted' => false,
                'approval_granted_at' => null,
            ]);

            QuoteActivity::query()->create([
                'quote_id' => $quote->id,
                'workspace_id' => $quote->workspace_id,
                'user_id' => $requestedBy,
                'type' => QuoteActivityType::ApprovalRequested->value,
                'description' => 'Approval requested before sending quote.',
                'metadata' => [
                    'rules_count' => $matchingRules->count(),
                    'rule_ids' => $matchingRules->pluck('id')->all(),
                    'approval_ids' => $approvalIds,
                ],
            ]);
        });

        if (! empty($approvalsToNotify)) {
            DB::afterCommit(function () use ($approvalsToNotify): void {
                foreach ($approvalsToNotify as $approval) {
                    if ($approval->approver) {
                        Notification::send($approval->approver, new QuoteApprovalRequestedNotification($approval));
                    }
                }
            });
        }
    }

    public function approveQuote(Quote $quote, int $userId, ?string $comment = null, bool $sendAfterApproval = false): void
    {
        $approvalsNotified = collect();

        DB::transaction(function () use ($quote, $userId, $comment, $sendAfterApproval, &$approvalsNotified) {
            $approvals = $quote->quoteApprovals()
                ->where('approver_id', $userId)
                ->where('status', QuoteApprovalStatus::Pending->value)
                ->get();

            foreach ($approvals as $approval) {
                $approval->approve($comment);
            }

            if ($approvals->isNotEmpty()) {
                $approvalsNotified = $approvals->load('approver');
                $metadata = array_filter([
                    'comment' => $comment,
                    'approval_ids' => $approvals->pluck('id')->all(),
                ], fn ($value) => $value !== null);

                QuoteActivity::query()->create([
                    'quote_id' => $quote->id,
                    'workspace_id' => $quote->workspace_id,
                    'user_id' => $userId,
                    'type' => QuoteActivityType::ApprovalApproved->value,
                    'description' => 'Approval recorded.',
                    'metadata' => $metadata ?: null,
                ]);
            }

            $this->checkAllApprovalsCompleted($quote, $userId, $sendAfterApproval);
        });

        if ($approvalsNotified instanceof Collection && $approvalsNotified->isNotEmpty()) {
            $recipients = $this->quoteStakeholders($quote);

            if ($recipients->isNotEmpty()) {
                DB::afterCommit(function () use ($recipients, $approvalsNotified): void {
                    foreach ($approvalsNotified as $approval) {
                        Notification::send($recipients, new QuoteApprovalApprovedNotification($approval));
                    }
                });
            }
        }
    }

    public function rejectQuote(Quote $quote, int $userId, ?string $comment = null): void
    {
        $rejectedApprovals = collect();

        DB::transaction(function () use ($quote, $userId, $comment, &$rejectedApprovals) {
            $approvals = $quote->quoteApprovals()
                ->where('approver_id', $userId)
                ->where('status', QuoteApprovalStatus::Pending->value)
                ->get();

            foreach ($approvals as $approval) {
                $approval->reject($comment);
            }

            $quote->update([
                'status' => QuoteStatus::Draft->value,
                'approval_granted' => false,
                'approval_granted_at' => null,
            ]);

            if ($approvals->isNotEmpty()) {
                $rejectedApprovals = $approvals->load('approver');
                $metadata = array_filter([
                    'comment' => $comment,
                    'approval_ids' => $approvals->pluck('id')->all(),
                ], fn ($value) => $value !== null);

                QuoteActivity::query()->create([
                    'quote_id' => $quote->id,
                    'workspace_id' => $quote->workspace_id,
                    'user_id' => $userId,
                    'type' => QuoteActivityType::ApprovalRejected->value,
                    'description' => 'Approval rejected.',
                    'metadata' => $metadata ?: null,
                ]);
            }
        });

        if ($rejectedApprovals instanceof Collection && $rejectedApprovals->isNotEmpty()) {
            $recipients = $this->quoteStakeholders($quote);

            if ($recipients->isNotEmpty()) {
                DB::afterCommit(function () use ($recipients, $rejectedApprovals): void {
                    foreach ($rejectedApprovals as $approval) {
                        Notification::send($recipients, new QuoteApprovalRejectedNotification($approval));
                    }
                });
            }
        }
    }

    private function checkAllApprovalsCompleted(Quote $quote, ?int $actedBy = null, bool $sendAfterApproval = false): void
    {
        $pendingApprovals = $quote->quoteApprovals()
            ->where('status', QuoteApprovalStatus::Pending->value)
            ->count();

        $rejectedApprovals = $quote->quoteApprovals()
            ->where('status', QuoteApprovalStatus::Rejected->value)
            ->count();

        if ($rejectedApprovals > 0) {
            $quote->update([
                'status' => QuoteStatus::Draft->value,
                'approval_granted' => false,
                'approval_granted_at' => null,
            ]);
        } elseif ($pendingApprovals === 0) {
            $grantedAt = now();

            if ($sendAfterApproval) {
                // When sending after approval, set status to Sent instead of Draft
                $quote->update([
                    'status' => QuoteStatus::Sent->value,
                    'sent_at' => $grantedAt,
                    'approval_granted' => true,
                    'approval_granted_at' => $grantedAt,
                ]);

                QuoteActivity::query()->create([
                    'quote_id' => $quote->id,
                    'workspace_id' => $quote->workspace_id,
                    'user_id' => $actedBy,
                    'type' => QuoteActivityType::ApprovalGranted->value,
                    'description' => 'All approvals granted and quote sent.',
                    'metadata' => [
                        'approval_granted_at' => $grantedAt->toIso8601String(),
                        'sent_after_approval' => true,
                    ],
                ]);

                // Trigger sending logic after transaction commits
                DB::afterCommit(function () use ($quote, $actedBy): void {
                    $this->sendQuoteAfterApproval($quote, $actedBy);
                });
            } else {
                // Normal approval - return to Draft
                $quote->update([
                    'status' => QuoteStatus::Draft->value,
                    'approval_granted' => true,
                    'approval_granted_at' => $grantedAt,
                ]);

                QuoteActivity::query()->create([
                    'quote_id' => $quote->id,
                    'workspace_id' => $quote->workspace_id,
                    'user_id' => $actedBy,
                    'type' => QuoteActivityType::ApprovalGranted->value,
                    'description' => 'All approvals granted.',
                    'metadata' => [
                        'approval_granted_at' => $grantedAt->toIso8601String(),
                    ],
                ]);
            }

            $recipients = $this->quoteStakeholders($quote);

            if ($recipients->isNotEmpty()) {
                DB::afterCommit(function () use ($recipients, $quote, $actedBy, $grantedAt): void {
                    $grantedByName = null;

                    if ($actedBy !== null) {
                        $grantedByName = optional($quote->quoteApprovals()->where('approver_id', $actedBy)->first()?->approver)->name;
                    }

                    Notification::send(
                        $recipients,
                        new QuoteApprovalGrantedNotification(
                            $quote,
                            $grantedByName,
                            $grantedAt,
                        ),
                    );
                });
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function quoteStakeholders(Quote $quote): Collection
    {
        return collect([$quote->creator, $quote->assignee])
            ->filter()
            ->unique('id')
            ->values();
    }

    private function sendQuoteAfterApproval(Quote $quote, ?int $actedBy = null): void
    {
        $quote->loadMissing(['workspace']);

        $this->quoteSendingService->sendQuote(
            quote: $quote,
            workspace: $quote->workspace,
            userId: $actedBy,
        );
    }
}
