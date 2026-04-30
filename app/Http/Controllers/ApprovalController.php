<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRule;
use App\Models\QuoteApproval;
use App\Models\Workspace;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function approve(Request $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'comment' => 'nullable|string|max:1000',
            'send' => 'nullable|boolean',
        ]);

        $this->approvalService->approveQuote(
            $approval->quote,
            $request->user()->id,
            $request->input('comment'),
            $request->boolean('send', false)
        );

        $message = $request->boolean('send', false)
            ? 'Quote approved and sent successfully.'
            : 'Quote approved successfully.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function reject(Request $request, QuoteApproval $approval): RedirectResponse
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'comment' => 'required|string|max:1000',
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
        abort_unless($workspace instanceof Workspace, 403);

        $this->authorize('create', ApprovalRule::class);

        $request->validate([
            'trigger_type' => 'required|in:value_above,value_below,client,all_quotes',
            'threshold_value' => ['nullable', 'numeric', 'min:0', Rule::requiredIf(fn () => in_array($request->input('trigger_type'), ['value_above', 'value_below']))],
            'client_id' => ['nullable', Rule::requiredIf(fn () => $request->input('trigger_type') === 'client'), 'exists:clients,id'],
            'approver_id' => 'required|exists:users,id',
        ]);

        ApprovalRule::create([
            'workspace_id' => $workspace->id,
            'trigger_type' => $request->trigger_type,
            'threshold_value' => $request->filled('threshold_value') ? (float) $request->input('threshold_value') : null,
            'client_id' => $request->input('client_id') ?: null,
            'approver_id' => $request->approver_id,
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Approval rule created successfully.']);

        return back();
    }

    public function updateRule(Request $request, ApprovalRule $rule): RedirectResponse
    {
        $this->authorize('update', $rule);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

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
