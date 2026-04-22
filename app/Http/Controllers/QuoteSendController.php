<?php

namespace App\Http\Controllers;

use App\Jobs\SendQuoteEmailJob;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use App\Notifications\QuoteSentInternalNotification;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QuoteSendController extends Controller
{
    public function store(
        Request $request,
        Quote $quote,
        WorkspaceSettingsService $workspaceSettingsService,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quote->loadMissing(['client', 'sections.lineItems']);

        $to = $quote->client?->email;

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
        $logoPath = $brandFields->get('logo_path')['value'] ?? null;
        $logoUrl = is_string($logoPath) && $logoPath !== '' ? Storage::url($logoPath) : null;

        $subjectTemplate = $emailFields->get('quote_email_subject')['value'] ?? 'Your Quote {quote_number} from {company_name}';
        $bodyTemplate = $emailFields->get('quote_email_template')['value'] ?? "Hi {client_name},\n\nPlease review quote {quote_number} totaling {quote_total}.";

        $merge = [
            '{client_name}' => (string) ($quote->client?->contact_name ?: $quote->client?->company_name ?: 'Client'),
            '{quote_number}' => (string) ($quote->number ?? 'Draft'),
            '{quote_total}' => number_format((float) $quote->total, 2).' '.($quote->currency ?? ''),
            '{valid_until}' => (string) ($quote->valid_until?->toDateString() ?? 'N/A'),
            '{company_name}' => $companyName,
            '{number}' => (string) ($quote->number ?? 'Draft'),
            '{company}' => $companyName,
        ];

        $subjectLine = strtr($subjectTemplate, $merge);
        $messageBody = strtr($bodyTemplate, $merge);

        $sendAt = now();
        $viewUrl = route('public-quotes.show', ['quoteUuid' => $quote->quote_uuid]);
        $unsubscribeUrl = url('/unsubscribe?email='.urlencode($to));

        SendQuoteEmailJob::dispatch(
            quoteId: $quote->id,
            to: $to,
            cc: [],
            subjectLine: $subjectLine,
            messageBody: $messageBody,
            companyName: $companyName,
            logoUrl: $logoUrl,
            viewUrl: $viewUrl,
            unsubscribeUrl: $unsubscribeUrl,
        )->delay($sendAt);

        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $sendAt,
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'type' => 'sent',
            'description' => 'Quote sent to client.',
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
            new QuoteSentInternalNotification(
                quote: $quote,
                scheduled: false,
                scheduledAt: null,
            ),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Quote sent successfully.'),
        ]);

        return back();
    }
}
