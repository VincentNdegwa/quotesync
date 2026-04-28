<?php

namespace App\Services;

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteActivityType;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\QuoteActivity;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
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

        DB::transaction(function () use ($quote, $matchingRules, $requestedBy): void {
            $approvalIds = [];

            foreach ($matchingRules as $rule) {
                $approval = QuoteApproval::create([
                    'quote_id' => $quote->id,
                    'approval_rule_id' => $rule->id,
                    'approver_id' => $rule->approver_id,
                    'status' => QuoteApprovalStatus::Pending->value,
                ]);
                $approvalIds[] = $approval->id;
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
    }

    public function approveQuote(Quote $quote, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($quote, $userId, $comment) {
            $approvals = $quote->quoteApprovals()
                ->where('approver_id', $userId)
                ->where('status', QuoteApprovalStatus::Pending->value)
                ->get();

            foreach ($approvals as $approval) {
                $approval->approve($comment);
            }

            if ($approvals->isNotEmpty()) {
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

            $this->checkAllApprovalsCompleted($quote, $userId);
        });
    }

    public function rejectQuote(Quote $quote, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($quote, $userId, $comment) {
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
    }

    private function checkAllApprovalsCompleted(Quote $quote, ?int $actedBy = null): void
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
    }
}
