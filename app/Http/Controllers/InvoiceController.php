<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
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

        $invoices = $workspace->invoices()
            ->with(['client:id,company_name', 'quote:id,number'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'title' => $invoice->title,
                'status' => $invoice->status->value,
                'total' => $invoice->total,
                'balance_due' => $invoice->balance_due,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'client' => $invoice->client?->company_name,
                'quote_number' => $invoice->quote?->number,
            ])->all();

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load(['client', 'quote', 'lineItems', 'createdBy']);

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'invoiceStatuses' => InvoiceStatus::all(),
        ]);
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
}
