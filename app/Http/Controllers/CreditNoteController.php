<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteLineItem;
use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreditNoteController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:full,partial,line_item',
            'title' => 'required|string|max:255',
            'reason' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'line_items' => 'required|array',
            'line_items.*.name' => 'required|string',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.quantity' => 'required|numeric|min:0',
            'line_items.*.unit' => 'nullable|string',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_amount' => 'required|numeric|min:0',
            'line_items.*.subtotal' => 'required|numeric|min:0',
            'line_items.*.total' => 'required|numeric|min:0',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        abort_unless($invoice->workspace_id === $workspace->id, 404);

        $client = Client::findOrFail($validated['client_id']);
        abort_unless($client->workspace_id === $workspace->id, 404);

        // Generate credit note number
        $creditNoteNumber = $this->generateCreditNoteNumber($workspace, $validated['currency']);

        $creditNote = DB::transaction(function () use ($validated, $workspace, $invoice, $client, $creditNoteNumber) {
            $creditNote = CreditNote::create([
                'workspace_id' => $workspace->id,
                'invoice_id' => $validated['invoice_id'],
                'client_id' => $validated['client_id'],
                'created_by' => $request->user()?->id,
                'credit_note_number' => $creditNoteNumber,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'currency' => $validated['currency'],
                'amount' => $validated['amount'],
                'tax_amount' => $validated['tax_amount'],
                'total' => $validated['total'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
            ]);

            foreach ($validated['line_items'] as $itemData) {
                CreditNoteLineItem::create([
                    'credit_note_id' => $creditNote->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'tax_amount' => $itemData['tax_amount'],
                    'subtotal' => $itemData['subtotal'],
                    'total' => $itemData['total'],
                ]);
            }

            return $creditNote;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note created.')]);

        return redirect()->route('credit-notes.show', $creditNote);
    }

    public function edit(Request $request, CreditNote $creditNote): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        $creditNote->load(['client', 'invoice', 'lineItems']);

        return Inertia::render('credit-notes/Edit', [
            'creditNote' => $creditNote,
        ]);
    }

    public function update(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status === 'applied' || $creditNote->status === 'voided') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot edit applied or voided credit notes.')]);
            return back();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'reason' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'line_items' => 'required|array',
            'line_items.*.id' => 'sometimes|integer|exists:credit_note_line_items,id',
            'line_items.*.name' => 'required|string',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.quantity' => 'required|numeric|min:0',
            'line_items.*.unit' => 'nullable|string',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.tax_amount' => 'required|numeric|min:0',
            'line_items.*.subtotal' => 'required|numeric|min:0',
            'line_items.*.total' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $creditNote) {
            $creditNote->update([
                'title' => $validated['title'],
                'reason' => $validated['reason'],
                'amount' => $validated['amount'],
                'tax_amount' => $validated['tax_amount'],
                'total' => $validated['total'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
            ]);

            $existingLineItemIds = [];
            foreach ($validated['line_items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $existingLineItemIds[] = $itemData['id'];
                    CreditNoteLineItem::where('id', $itemData['id'])
                        ->where('credit_note_id', $creditNote->id)
                        ->update([
                            'name' => $itemData['name'],
                            'description' => $itemData['description'] ?? null,
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'] ?? null,
                            'unit_price' => $itemData['unit_price'],
                            'tax_amount' => $itemData['tax_amount'],
                            'subtotal' => $itemData['subtotal'],
                            'total' => $itemData['total'],
                        ]);
                } else {
                    CreditNoteLineItem::create([
                        'credit_note_id' => $creditNote->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? null,
                        'unit_price' => $itemData['unit_price'],
                        'tax_amount' => $itemData['tax_amount'],
                        'subtotal' => $itemData['subtotal'],
                        'total' => $itemData['total'],
                    ]);
                }
            }

            CreditNoteLineItem::where('credit_note_id', $creditNote->id)
                ->whereNotIn('id', $existingLineItemIds)
                ->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note updated.')]);

        return back();
    }

    public function issue(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status !== 'draft') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Only draft credit notes can be issued.')]);
            return back();
        }

        $creditNote->update(['status' => 'issued']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note issued.')]);

        return back();
    }

    public function apply(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status !== 'issued') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Only issued credit notes can be applied.')]);
            return back();
        }

        DB::transaction(function () use ($creditNote) {
            $creditNote->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            // Update invoice amount_credited
            if ($creditNote->invoice) {
                $invoice = $creditNote->invoice;
                $invoice->updateQuietly([
                    'amount_credited' => $invoice->amount_credited + $creditNote->total,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note applied to invoice.')]);

        return back();
    }

    public function void(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $creditNote->workspace_id === $workspace->id, 404);

        if ($creditNote->status === 'voided') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Credit note is already voided.')]);
            return back();
        }

        DB::transaction(function () use ($creditNote) {
            $creditNote->update(['status' => 'voided']);

            // If credit note was applied, revert the amount_credited
            if ($creditNote->status === 'applied' && $creditNote->invoice) {
                $invoice = $creditNote->invoice;
                $invoice->updateQuietly([
                    'amount_credited' => max(0, $invoice->amount_credited - $creditNote->total),
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Credit note voided.')]);

        return back();
    }

    protected function generateCreditNoteNumber(Workspace $workspace, string $currency): string
    {
        $year = now()->format('Y');
        
        $lastCreditNote = CreditNote::where('workspace_id', $workspace->id)
            ->where('currency', $currency)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCreditNote) {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastCreditNote->credit_note_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('CN-%s-%03d', $year, $nextNumber);
    }
}
