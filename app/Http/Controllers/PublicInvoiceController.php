<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Invoices\InvoiceService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvoiceController extends Controller
{
    public function show(
        string $invoiceUuid,
        Request $request,
        WorkspaceSettingsService $workspaceSettingsService,
        InvoiceService $invoiceService,
    ): Response {
        $invoice = Invoice::query()
            ->where('invoice_uuid', $invoiceUuid)
            ->firstOrFail();

        return Inertia::render('public/InvoiceView', [
            'invoice' => $invoiceService->toBuilderPayload($invoice),
            'invoice_uuid' => $invoice->invoice_uuid,
            'settings' => $workspaceSettingsService->builderSettings($invoice->workspace),
            'clientState' => $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'void' ? 'closed' : 'open'),
            'isWorkspaceMember' => $request->user()?->currentWorkspace?->id === $invoice->workspace_id,
        ]);
    }
}
