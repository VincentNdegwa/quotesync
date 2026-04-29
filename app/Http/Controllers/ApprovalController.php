<?php

namespace App\Http\Controllers;

use App\Enums\QuoteApprovalStatus;
use App\Models\ApprovalRule;
use App\Models\Client;
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
                        'total' => (float) $quote->total,
                        'currency' => $quote->currency ?? $workspaceCurrency,
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

        $rules = ApprovalRule::query()
            ->where('workspace_id', $workspace->id)
            ->with(['client:id,company_name', 'approver:id,name'])
            ->orderBy('id')
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

        return Inertia::render('approvals/Index', [
            'pendingApprovals' => $pendingApprovals,
            'rules' => $rules,
            'approvers' => $approvers,
            'clients' => $clients,
            'currency' => $workspaceCurrency,
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
