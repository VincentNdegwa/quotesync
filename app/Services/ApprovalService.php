<?php

namespace App\Services;

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Quote;
use App\Models\QuoteApproval;
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

    public function initiateApproval(Quote $quote): void
    {
        $matchingRules = ApprovalRule::where('workspace_id', $quote->workspace_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($rule) => $rule->matches($quote));

        DB::transaction(function () use ($quote, $matchingRules) {
            foreach ($matchingRules as $rule) {
                QuoteApproval::create([
                    'quote_id' => $quote->id,
                    'approval_rule_id' => $rule->id,
                    'approver_id' => $rule->approver_id,
                    'status' => QuoteApprovalStatus::Pending->value,
                ]);
            }

            $quote->update(['status' => QuoteStatus::PendingApproval->value]);
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

            $this->checkAllApprovalsCompleted($quote);
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

            $quote->update(['status' => QuoteStatus::Draft->value]);
        });
    }

    private function checkAllApprovalsCompleted(Quote $quote): void
    {
        $pendingApprovals = $quote->quoteApprovals()
            ->where('status', QuoteApprovalStatus::Pending->value)
            ->count();

        $rejectedApprovals = $quote->quoteApprovals()
            ->where('status', QuoteApprovalStatus::Rejected->value)
            ->count();

        if ($rejectedApprovals > 0) {
            $quote->update(['status' => QuoteStatus::Draft->value]);
        } elseif ($pendingApprovals === 0) {
            $quote->update(['status' => QuoteStatus::Sent->value]);
        }
    }
}
