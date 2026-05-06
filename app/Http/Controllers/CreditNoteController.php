<?php

namespace App\Http\Controllers;

use App\Enums\CreditNoteStatus;
use App\Http\Requests\CreditNotes\StoreCreditNoteRequest;
use App\Http\Requests\CreditNotes\UpdateCreditNoteRequest;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Services\CreditNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreditNoteController extends Controller
{
    public function __construct(
        private CreditNoteService $creditNoteService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $sort = $request->get('sort', 'newest');

        $query = CreditNote::where('workspace_id', $workspace->id)
            ->with(['client', 'invoice']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        match ($sort) {
            'number' => $query->orderBy('credit_note_number'),
            'amount' => $query->orderBy('total', 'desc'),
            'issue_date' => $query->orderBy('issue_date'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $creditNotes = $query->paginate(15);

        return Inertia::render('credit-notes/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'creditNotes' => $creditNotes,
        ]);
    }

    public function show(Request $request, CreditNote $creditNote): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        $creditNote->load(['client', 'invoice', 'lineItems', 'createdBy']);

        return Inertia::render('credit-notes/Show', [
            'creditNote' => $creditNote,
        ]);
    }

    public function create(Request $request, Invoice $invoice): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load(['client', 'lineItems']);

        return Inertia::render('credit-notes/Create', [
            'invoice' => $invoice,
        ]);
    }

    public function store(StoreCreditNoteRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        try {
            $creditNote = $this->creditNoteService->create(
                $request->validated(),
                $workspace,
                $request->user()?->id
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note created.')]);

            return redirect()->route('credit-notes.show', $creditNote);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function edit(Request $request, CreditNote $creditNote): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        $creditNote->load(['client', 'invoice.client', 'invoice.lineItems', 'lineItems']);

        return Inertia::render('credit-notes/Edit', [
            'creditNote' => $creditNote,
        ]);
    }

    public function update(UpdateCreditNoteRequest $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status === CreditNoteStatus::Issued || $creditNote->status === CreditNoteStatus::Voided) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot edit issued or voided credit notes.')]);
            return back();
        }

        try {
            $this->creditNoteService->update($creditNote, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note updated.')]);

            return back();
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function issue(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status !== CreditNoteStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Only draft credit notes can be issued.')]);
            return back();
        }

        try {
            $this->creditNoteService->issue($creditNote);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note issued.')]);

            return back();
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function void(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status === CreditNoteStatus::Voided) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Credit note is already voided.')]);
            return back();
        }

        try {
            $validated = $request->validate([
                'void_reason' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessage = collect($errors)->flatten()->implode(', ');
            Inertia::flash('toast', ['type' => 'error', 'message' => $errorMessage]);
            return back()->withErrors($errors);
        }

        try {
            $this->creditNoteService->void($creditNote, $validated['void_reason']);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note voided.')]);

            return back();
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }
}
