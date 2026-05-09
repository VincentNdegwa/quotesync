<?php

namespace App\Http\Controllers;

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\Workspace;
use App\Services\ApprovalService;
use App\Services\Quotes\QuoteSendingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteSendController extends Controller
{
    public function __construct(
        private QuoteSendingService $quoteSendingService,
        private ApprovalService $approvalService,
    ) {}

    public function store(
        Request $request,
        Quote $quote,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $currentStatus = $quote->status instanceof QuoteStatus
            ? $quote->status
            : QuoteStatus::from((string) $quote->status);

        if ($currentStatus === QuoteStatus::PendingApproval) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('Quote is pending approval and cannot be sent yet.'),
            ]);

            return back();
        }

        $approvalRequired = $quote->approval_granted !== true
            && $this->approvalService->checkApprovalRequired($quote);

        if ($approvalRequired) {
            $hasPendingApprovals = QuoteApproval::query()
                ->where('quote_id', $quote->id)
                ->where('status', QuoteApprovalStatus::Pending->value)
                ->exists();

            if (! $hasPendingApprovals) {
                $this->approvalService->initiateApproval($quote, $request->user()->id);
            }

            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Quote requires approval before it can be sent. Approval requests have been created.'),
            ]);

            return back();
        }

        $quote->loadMissing(['client']);

        if (empty($quote->client?->email)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Client does not have an email address.'),
            ]);

            return back();
        }

        $sendAt = now();

        $this->quoteSendingService->sendQuote(
            quote: $quote,
            workspace: $workspace,
            userId: $request->user()?->id,
            attachPdf: $request->boolean('attach_pdf', false),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $quote->forceFill([
            'status' => QuoteStatus::Sent->value,
            'sent_at' => $sendAt,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Quote sent successfully.'),
        ]);

        return back();
    }
}
