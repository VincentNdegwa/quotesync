<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRule;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalService $approvalService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof \App\Models\Workspace, 403);

        $pendingApprovals = QuoteApproval::query()
            ->whereHas('quote', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->with(['quote.client', 'approvalRule'])
            ->get();

        $rules = ApprovalRule::where('workspace_id', $workspace->id)
            ->with(['client', 'approver'])
            ->get();

        return Inertia::render('approvals/Index', [
            'pendingApprovals' => $pendingApprovals,
            'rules' => $rules,
        ]);
    }

    public function approve(Request $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->approvalService->approveQuote(
            $approval->quote,
            $request->user()->id,
            $request->input('comment')
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quote approved successfully.']);

        return back();
    }

    public function reject(Request $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->approvalService->rejectQuote(
            $approval->quote,
            $request->user()->id,
            $request->input('comment')
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quote rejected.']);

        return back();
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof \App\Models\Workspace, 403);

        $request->validate([
            'trigger_type' => 'required|in:value_above,value_below,client,all_quotes',
            'threshold_value' => 'nullable|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id',
            'approver_id' => 'required|exists:users,id',
        ]);

        ApprovalRule::create([
            'workspace_id' => $workspace->id,
            'trigger_type' => $request->trigger_type,
            'threshold_value' => $request->threshold_value,
            'client_id' => $request->client_id,
            'approver_id' => $request->approver_id,
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Approval rule created successfully.']);

        return back();
    }

    public function destroyRule(ApprovalRule $rule): RedirectResponse
    {
        $this->authorize('delete', $rule);

        $rule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Approval rule deleted.']);

        return back();
    }
}
