<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approvals\ApproveQuoteRequest;
use App\Http\Requests\Approvals\RejectQuoteRequest;
use App\Http\Requests\Approvals\StoreApprovalRuleRequest;
use App\Http\Requests\Approvals\UpdateApprovalRuleRequest;
use App\Models\ApprovalRule;
use App\Models\QuoteApproval;
use App\Models\Workspace;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalService $approvalService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;
        abort_unless($user && $workspace instanceof Workspace, 403);

        $isWorkspaceOwner = $workspace->owner_id === $user->id;

        abort_unless($isWorkspaceOwner || $user->belongsToWorkspace($workspace), 403);

        $data = $this->approvalService->index($workspace, $user);

        return Inertia::render('approvals/Index', $data);
    }

    public function approve(ApproveQuoteRequest $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $validated = $request->validated();
        $sendToClient = (bool) ($validated['send'] ?? false);

        $this->approvalService->approveQuote(
            $approval->quote,
            $request->user()->id,
            $validated['comment'] ?? null,
            $sendToClient
        );

        $message = $sendToClient
            ? 'Quote approved and sent successfully.'
            : 'Quote approved successfully.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function reject(RejectQuoteRequest $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $validated = $request->validated();

        $this->approvalService->rejectQuote(
            $approval->quote,
            $request->user()->id,
            $validated['comment']
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quote rejected.']);

        return back();
    }

    public function storeRule(StoreApprovalRuleRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $this->authorize('create', ApprovalRule::class);

        $validated = $request->validated();

        ApprovalRule::create([
            'workspace_id' => $workspace->id,
            'trigger_type' => $validated['trigger_type'],
            'threshold_value' => array_key_exists('threshold_value', $validated) && $validated['threshold_value'] !== null
                ? (float) $validated['threshold_value']
                : null,
            'client_id' => $validated['client_id'] ?? null,
            'approver_id' => $validated['approver_id'],
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Approval rule created successfully.']);

        return back();
    }

    public function updateRule(UpdateApprovalRuleRequest $request, ApprovalRule $rule): RedirectResponse
    {
        $this->authorize('update', $rule);

        $validated = $request->validated();

        $rule->update(['is_active' => (bool) $validated['is_active']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Approval rule updated.']);

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
