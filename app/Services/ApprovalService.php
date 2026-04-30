<?php

namespace App\Services;

use App\Enums\QuoteActivityType;
use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\QuoteApproval;
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
