<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Workspace;
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
    ): Response {
        $invoice = Invoice::query()
            ->with([
                'client',
                'workspace',
                'lineItems',
                'createdBy:id,name,email',
            ])
            ->where('invoice_uuid', $invoiceUuid)
            ->firstOrFail();

        $invoice->loadMissing(['client:id,company_name,contact_name,email', 'workspace:id,name,display_name']);

        return Inertia::render('public/InvoiceView', [
            'invoice' => $invoice,
            'invoice_uuid' => $invoice->invoice_uuid,
            'settings' => $workspaceSettingsService->builderSettings($invoice->workspace),
        ]);
    }
}
