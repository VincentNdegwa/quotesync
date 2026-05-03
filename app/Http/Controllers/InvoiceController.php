<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceActivityType;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\InvoicePayment;
use App\Models\Quote;
use App\Models\Workspace;
use App\Models\Comment;
use App\Services\Invoices\InvoiceNumberingService;
use App\Services\BuilderLookupService;
use App\Services\Invoices\InvoiceService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response | JsonResponse
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

        if ($request->wantsJson()) {
            return response()->json($invoices);
        }

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

    public function show(Request $request, Invoice $invoice, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load([
            'client',
            'quote',
            'lineItems',
            'createdBy',
            'activities.user',
            'payments.createdBy:id,name',
            'comments.user:id,name',
        ]);

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'invoiceStatuses' => InvoiceStatus::all(),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
        ]);
    }

    public function edit(Request $request, Invoice $invoice, InvoiceService $invoiceService, WorkspaceSettingsService $workspaceSettingsService, BuilderLookupService $builderLookupService): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        return Inertia::render('invoices/Edit', [
            'initialState' => $invoiceService->toBuilderPayload($invoice),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            ...$builderLookupService->getBasicLookups($workspace),
            'invoiceId' => $invoice->id,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        abort_unless($invoice->status->canBeEdited(), 403, 'Only draft invoices can be edited.');

        $invoiceService->update($invoice, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice updated.')]);

        return back();
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

        // Track activity based on status change
        $activityType = match ($newStatus) {
            InvoiceStatus::Sent => InvoiceActivityType::Sent,
            InvoiceStatus::Paid => InvoiceActivityType::Paid,
            InvoiceStatus::Void => InvoiceActivityType::Voided,
            default => null,
        };

        if ($activityType) {
            InvoiceActivity::query()->create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => $activityType->value,
                'description' => "Invoice status changed from {$currentStatus->value} to {$newStatus->value}",
                'metadata' => ['previous_status' => $currentStatus->value, 'new_status' => $newStatus->value],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

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

        InvoiceActivity::query()->create([
            'invoice_id' => $invoice->id,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'type' => InvoiceActivityType::Created->value,
            'description' => 'Invoice created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Inertia::flash('toast', ['type'=> 'success', 'message'=> __("Invoice created successfully")]);

        return redirect()->route('invoices.show', $invoice);
    }

    public function convertFromQuote(Request $request, Quote $quote, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
        abort_unless($quote->status === QuoteStatus::Won, 403);

        $invoice = $invoiceService->convertFromQuote($quote, $request->user()?->id);

        InvoiceActivity::query()->create([
            'invoice_id' => $invoice->id,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'type' => InvoiceActivityType::Created->value,
            'description' => 'Invoice created from quote',
            'metadata' => ['quote_id' => $quote->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice created from quote..')]);

        return redirect()->route('invoices.show', $invoice);
    }

    public function duplicate(Request $request, Invoice $invoice, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $newInvoice = $invoiceService->duplicate($invoice);

        InvoiceActivity::query()->create([
            'invoice_id' => $newInvoice->id,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'type' => InvoiceActivityType::Created->value,
            'description' => 'Invoice duplicated',
            'metadata' => ['original_invoice_id' => $invoice->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

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
            'type' => InvoiceActivityType::Voided->value,
            'description' => 'Invoice archived',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
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

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment = $invoice->payments()->create([
            'workspace_id' => $workspace->id,
            'created_by' => auth()->id(),
            'amount' => $validated['amount'],
            'currency' => $invoice->currency,
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $totalPaid = $invoice->payments()->sum('amount');
        $invoice->update([
            'paid_amount' => $totalPaid,
            'balance_due' => $invoice->total - $totalPaid,
        ]);

        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => InvoiceStatus::Paid->value]);

            InvoiceActivity::query()->create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => InvoiceActivityType::Paid->value,
                'description' => 'Payment recorded: ' . number_format($validated['amount'], 2),
                'metadata' => ['payment_id' => $payment->id, 'amount' => $validated['amount']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } else {
            InvoiceActivity::query()->create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => InvoiceActivityType::Partial->value,
                'description' => 'Partial payment recorded: ' . number_format($validated['amount'], 2),
                'metadata' => ['payment_id' => $payment->id, 'amount' => $validated['amount']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return back()->with('success', 'Payment recorded successfully');
    }

    public function refundPayment(Request $request, InvoicePayment $payment): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $payment->invoice->workspace_id === $workspace->id, 404);
        abort_if($payment->is_refund, 403, 'This payment is already a refund');

        $validated = $request->validate([
            'refund_reason' => 'required|string|max:1000',
        ]);

        $payment->update([
            'is_refund' => true,
            'refunded_at' => now(),
            'refund_reason' => $validated['refund_reason'],
            'refunded_by' => auth()->id(),
        ]);

        // Recalculate invoice totals
        $invoice = $payment->invoice;
        $totalPaid = $invoice->payments()->where('is_refund', false)->sum('amount');
        $invoice->update([
            'paid_amount' => $totalPaid,
            'balance_due' => $invoice->total - $totalPaid,
        ]);

        return back()->with('success', 'Payment refunded successfully');
    }
}
