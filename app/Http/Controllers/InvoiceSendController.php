<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use App\Models\Workspace;
use App\Notifications\InvoiceSentInternalNotification;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvoiceSendController extends Controller
{
    public function store(
        Request $request,
        Invoice $invoice,
        WorkspaceSettingsService $workspaceSettingsService,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $invoice->workspace_id === $workspace->id, 404);

        $invoice->load(['client', 'lineItems']);

        $to = $invoice->client?->email;

        if (empty($to)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Client does not have an email address.'),
            ]);

            return back();
        }

        $emailFields = collect($workspaceSettingsService->groupForFrontend($workspace, 'email')['fields'] ?? [])->keyBy('key');
        $brandFields = collect($workspaceSettingsService->groupForFrontend($workspace, 'brand')['fields'] ?? [])->keyBy('key');

        $companyName = (string) ($brandFields->get('company_name')['value'] ?? config('app.name'));
        $logoUrl = $brandFields->get('logo_path')['value'] ?? null;

        $subjectTemplate = $emailFields->get('invoice_email_subject')['value'] ?? 'Your Invoice {invoice_number} from {company_name}';
        $bodyTemplate = $emailFields->get('invoice_email_template')['value'] ?? "Hi {client_name},\n\nPlease find invoice {invoice_number} totaling {invoice_total} attached.";

        $merge = [
            '{client_name}' => (string) ($invoice->client?->contact_name ?: $invoice->client?->company_name ?: 'Client'),
            '{invoice_number}' => (string) ($invoice->number ?? 'Draft'),
            '{invoice_total}' => number_format((float) $invoice->total, 2).' '.($invoice->currency ?? ''),
            '{due_date}' => (string) ($invoice->due_date?->toDateString() ?? 'N/A'),
            '{company_name}' => $companyName,
            '{number}' => (string) ($invoice->number ?? 'Draft'),
            '{company}' => $companyName,
        ];

        $subjectLine = strtr($subjectTemplate, $merge);
        $messageBody = strtr($bodyTemplate, $merge);

        if (empty($invoice->invoice_uuid)) {
            $invoice->invoice_uuid = (string) Str::uuid();
            $invoice->save();
        }

        $publicInvoiceUrl = url("/i/{$invoice->invoice_uuid}");

        $sendAt = now();

        SendInvoiceEmailJob::dispatch(
            invoiceId: $invoice->id,
            to: $to,
            cc: [],
            subjectLine: $subjectLine,
            messageBody: $messageBody,
            companyName: $companyName,
            logoUrl: $logoUrl,
            publicInvoiceUrl: $publicInvoiceUrl,
        )->delay($sendAt);

        $invoice->forceFill([
            'status' => 'sent',
            'sent_at' => $sendAt,
        ])->save();

        InvoiceActivity::query()->create([
            'invoice_id' => $invoice->id,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'type' => 'sent',
            'description' => 'Invoice sent to client.',
            'metadata' => [
                'to' => $to,
                'cc' => [],
                'channel' => 'email',
                'scheduled_at' => null,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Notification::send(
            $workspace->members()->get(),
            new InvoiceSentInternalNotification(
                invoice: $invoice,
                scheduled: false,
                scheduledAt: null,
            ),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice sent successfully.'),
        ]);

        return back();
    }
}
