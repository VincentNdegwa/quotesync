<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\Quote;
use App\Models\Workspace;
use App\Services\Invoices\InvoiceNumberingService;
use App\Services\Invoices\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $search = $request->get('search');
        $status = $request->get('status');
        $sort = $request->get('sort', 'newest');

        $query = $workspace->invoices()
            ->with(['client:id,company_name,email', 'quote:id,number']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== '__all__') {
            $query->where('status', $status);
        }

        match ($sort) {
            'number' => $query->orderBy('invoice_number'),
            'amount' => $query->orderBy('total', 'desc'),
            'due_date' => $query->orderBy('due_date'),
            default => $query->orderByDesc('created_at'),
        };

        $invoices = $query->paginate(15);

        return Inertia::render('invoices/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'invoices' => $invoices,
        ]);
    }

    public function kanban(Request $request): \Illuminate\Http\JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $invoices = $workspace->invoices()
            ->with(['client:id,company_name,email', 'quote:id,number'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'title' => $invoice->title,
                'status' => $invoice->status->value,
                'total' => $invoice->total,
                'base_total' => $invoice->base_total,
                'currency' => $invoice->currency,
                'base_currency' => $invoice->base_currency,
                'due_date' => $invoice->due_date?->toDateString(),
                'client' => $invoice->client?->company_name,
                'quote_number' => $invoice->quote?->number,
                'client' => $invoice->client,
            ])->toArray();

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load(['client', 'quote', 'lineItems', 'createdBy']);

        $settings = $workspace->settings()->pluck('value', 'key')->toArray();

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'invoiceStatuses' => InvoiceStatus::all(),
            'settings' => $settings,
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $validated = $request->validate(['status' => 'required|string']);
        $newStatus = InvoiceStatus::from($validated['status']);

        $currentStatus = $invoice->status;
        if (! in_array($newStatus, $currentStatus->allowedTransitions(), true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __("Invalid status transition from {$currentStatus->value} to {$newStatus->value}."),
            ]);
            return back();
        }

        $invoice->update(['status' => $newStatus->value]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice status updated.')]);

        return back();
    }

    public function store(StoreInvoiceRequest $request, InvoiceNumberingService $numberingService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        $invoice = Invoice::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $validated['client_id'],
            'quote_id' => $validated['quote_id'] ?? null,
            'invoice_number' => $numberingService->generateNextNumber($workspace),
            'title' => $validated['title'],
            'cover_message' => $validated['cover_message'] ?? null,
            'terms' => $validated['terms'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'subtotal' => $validated['subtotal'] ?? 0,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'total' => $validated['total'] ?? 0,
            'paid_amount' => 0,
            'balance_due' => $validated['total'] ?? 0,
            'status' => InvoiceStatus::Draft->value,
            'issue_date' => $validated['issue_date'] ?? now(),
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        foreach ($validated['line_items'] ?? [] as $item) {
            $invoice->lineItems()->create([
                'catalog_item_id' => $item['catalog_item_id'] ?? null,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'total' => $item['total'],
                'sort_order' => $item['sort_order'] ?? 0,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('toast', ['type' => 'success', 'message' => 'Invoice created.']);
    }

    public function convertFromQuote(Request $request, Quote $quote, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
        abort_unless($quote->status === QuoteStatus::Won, 403);

        $invoice = $invoiceService->convertFromQuote($quote, $request->user()?->id);

        return redirect()->route('invoices.show', $invoice)->with('toast', ['type' => 'success', 'message' => 'Invoice created from quote.']);
    }

    public function duplicate(Request $request, Invoice $invoice, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $newInvoice = $invoiceService->duplicate($invoice);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice duplicated successfully.')]);

        return to_route('invoices.edit', $newInvoice);
    }

    public function archive(Request $request, Invoice $invoice): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->delete();

        InvoiceActivity::query()->create([
            'invoice_id' => $invoice->id,
            'workspace_id' => $invoice->workspace_id,
            'user_id' => $request->user()?->id,
            'type' => 'created',
            'description' => 'Invoice archived',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice archived successfully.')]);

        return to_route('invoices.index');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice deleted successfully.')]);

        return to_route('invoices.index');
    }
}
