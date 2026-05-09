<?php

namespace App\Http\Controllers;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceActivityType;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\Invoices\InvoiceBulkActionRequest;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\InvoicePayment;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Invoices\InvoiceNumberingService;
use App\Services\BuilderLookupService;
use App\Services\Invoices\InvoiceService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $quoteId = $request->integer('quote');
        $view = $request->string('view')->toString();

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

        if ($quoteId) {
            $query->where('quote_id', $quoteId);
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

        $quoteFilter = null;

        if ($quoteId) {
            $quoteFilter = Quote::query()
                ->where('workspace_id', $workspace->id)
                ->select('id', 'number', 'title')
                ->find($quoteId);
        }

        return Inertia::render('invoices/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'quote' => $quoteId,
                'view' => $view,
            ],
            'quoteFilter' => $quoteFilter,
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
            ->get();

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load([
            'client',
            'quote',
            'sections.lineItems.taxes',
            'createdBy',
            'activities.user',
            'payments.createdBy:id,name',
            'comments.user:id,name',
            'creditNotes',
        ]);

        $invoice = $this->transformInvoice($invoice);

        $teamMembers = User::whereHas('workspaces', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id);
        })->select('id', 'name', 'email')->get();

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'invoiceStatuses' => InvoiceStatus::all(),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            'teamMembers' => $teamMembers,
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
            'invoice' => $invoice,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $invoiceService): RedirectResponse
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

            abort_unless($invoice->status->canBeEdited(), 403, 'Only draft invoices can be edited.');

            $invoiceService->update($invoice, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice updated.')]);

            return back();
        } catch (\Exception $e) {
            Log::error('Error updating invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to update invoice: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function bulkAction(InvoiceBulkActionRequest $request, InvoiceService $invoiceService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        $result = $invoiceService->bulkAction(
            $workspace,
            $validated['ids'],
            $validated['action'],
        );

        $processed = $result['processed'];
        $skipped = $result['skipped'];
        $missing = $result['missing'];

        $message = __('No invoices were updated.');
        $type = $processed > 0 ? 'success' : 'warning';

        if ($processed > 0) {
            $message = trans_choice(':count invoice processed.|:count invoices processed.', $processed, ['count' => $processed]);

            if ($skipped > 0) {
                $message .= ' ' . trans_choice(':count invoice skipped due to status restrictions.|:count invoices skipped due to status restrictions.', $skipped, ['count' => $skipped]);
            }
        } elseif ($skipped > 0) {
            $message = trans_choice('All selected invoices were skipped (:count affected).|All selected invoices were skipped (:count affected).', $skipped, ['count' => $skipped]);
        } elseif ($missing > 0) {
            $message = __('None of the selected invoices were found.');
        }

        Inertia::flash('toast', [
            'type' => $type,
            'message' => $message,
        ]);

        return back();
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error updating invoice status: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to update invoice status: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function store(StoreInvoiceRequest $request, InvoiceNumberingService $numberingService): RedirectResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type'=> 'error', 'message'=> __("Failed to create invoice: " . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function convertFromQuote(Request $request, Quote $quote, InvoiceService $invoiceService): RedirectResponse
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
            abort_unless($quote->status === QuoteStatus::Won, 403);

            $invoice = $invoiceService->convertFromQuote($quote, $request->user()?->id);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice created from quote successfully.')]);

            return redirect()->route('invoices.edit', $invoice);
        } catch (\Exception $e) {
            Log::error('Error converting quote to invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'quote_id' => $quote->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to convert quote to invoice: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function duplicate(Request $request, Invoice $invoice, InvoiceService $invoiceService): RedirectResponse
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

            $newInvoice = $invoiceService->duplicate($invoice);

            InvoiceActivity::query()->create([
                'invoice_id' => $newInvoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => InvoiceActivityType::Created->value,
                'description' => 'Invoice duplicated',
                'metadata' => ['parent_invoice_id' => $invoice->id],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice duplicated successfully.')]);

            return redirect()->route('invoices.edit', $newInvoice);
        } catch (\Exception $e) {
            Log::error('Error duplicating invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to duplicate invoice: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function archive(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

            $invoice->delete();

            InvoiceActivity::query()->create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => InvoiceActivityType::Voided->value,
                'description' => 'Invoice archived',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice archived successfully.')]);

            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            Log::error('Error archiving invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to archive invoice: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

            $invoice->forceDelete();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice deleted successfully.')]);

            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to delete invoice: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
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
            $totalCredited = $invoice->creditNotes()->whereIn('status', CreditNoteStatus::creditedStatuses())->sum('total');
            $invoice->update([
                'paid_amount' => $totalPaid,
                'amount_credited' => $totalCredited,
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
            }

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded successfully.')]);

            return back();
        } catch (\Exception $e) {
            Log::error('Error recording payment: ' . $e->getMessage(), [
                'exception' => $e,
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to record payment: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    public function refundPayment(Request $request, InvoicePayment $payment): RedirectResponse
    {
        try {
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
            $totalCredited = $invoice->creditNotes()->whereIn('status', CreditNoteStatus::creditedStatuses())->sum('total');
            $invoice->update([
                'paid_amount' => $totalPaid,
                'amount_credited' => $totalCredited,
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment refunded successfully.')]);

            return back();
        } catch (\Exception $e) {
            Log::error('Error refunding payment: ' . $e->getMessage(), [
                'exception' => $e,
                'payment_id' => $payment->id,
                'request' => $request->all(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to refund payment: ' . $e->getMessage())]);
            return back()->withInput();
        }
    }

    private function transformInvoice(Invoice $invoice): Invoice
    {
        $invoice->subtotal = $invoice->base_subtotal;
        $invoice->discount_amount = $invoice->base_discount_amount;
        $invoice->tax_amount = $invoice->base_tax_amount;
        $invoice->total = $invoice->base_total;
        $invoice->currency = $invoice->base_currency;

        $baseSubtotal = 0;
        $baseTaxAmount = 0;

        foreach ($invoice->sections as $section) {
            foreach ($section->lineItems as $lineItem) {
                $lineItem->unit_price = $lineItem->base_unit_price ?? $lineItem->unit_price;
                $lineItem->subtotal = $lineItem->base_subtotal ?? $lineItem->subtotal;
                $lineItem->tax_amount = $lineItem->base_tax_amount ?? $lineItem->tax_amount;
                $lineItem->total = $lineItem->base_total ?? $lineItem->total;

                $baseSubtotal += $lineItem->base_subtotal ?? 0;
                $baseTaxAmount += $lineItem->base_tax_amount ?? 0;

                foreach ($lineItem->taxes as $tax) {
                    $tax->tax_amount = $tax->base_tax_amount ?? $tax->tax_amount;
                }
            }
        }

        $invoice->base_subtotal = $baseSubtotal;
        $invoice->base_tax_amount = $baseTaxAmount;
        $invoice->base_total = $baseSubtotal + $baseTaxAmount - $invoice->base_discount_amount;
        $invoice->subtotal = $invoice->base_subtotal;
        $invoice->tax_amount = $invoice->base_tax_amount;
        $invoice->total = $invoice->base_total;
        $invoice->currency = $invoice->base_currency;

        $invoice->paid_amount = $invoice->paid_amount > 0 ? $invoice->paid_amount / ($invoice->fx_rate ?? 1) : 0;

        $invoice->amount_credited = $invoice->creditNotes()
            ->whereIn('status', CreditNoteStatus::creditedStatuses())
            ->sum('base_total');

        return $invoice;
    }
}
