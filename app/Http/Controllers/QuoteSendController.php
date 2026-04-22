<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendQuoteRequest;
use App\Jobs\SendQuoteEmailJob;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use App\Notifications\QuoteSentInternalNotification;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QuoteSendController extends Controller
{
    public function store(
        SendQuoteRequest $request,
        Quote $quote,
        WorkspaceSettingsService $workspaceSettingsService,
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quote->loadMissing(['client', 'sections.lineItems']);

        $emailFields = collect($workspaceSettingsService->groupForFrontend($workspace, 'email')['fields'] ?? [])->keyBy('key');
        $brandFields = collect($workspaceSettingsService->groupForFrontend($workspace, 'brand')['fields'] ?? [])->keyBy('key');

        $companyName = (string) ($brandFields->get('company_name')['value'] ?? config('app.name'));
        $logoPath = $brandFields->get('logo_path')['value'] ?? null;
        $logoUrl = is_string($logoPath) && $logoPath !== '' ? Storage::url($logoPath) : null;

        $subjectTemplate = trim((string) $request->string('subject'));
        $bodyTemplate = (string) ($request->input('message_body') ?? ($emailFields->get('quote_email_template')['value'] ?? ''));

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

        $cc = collect(Arr::wrap($request->input('cc', [])))
            ->map(fn ($email): string => trim((string) $email))
            ->filter()
            ->values()
            ->all();

        $scheduleEnabled = (bool) $request->boolean('schedule_enabled');
        $sendAt = $scheduleEnabled && $request->filled('send_at')
            ? Carbon::parse((string) $request->input('send_at'))
            : now();

        $viewUrl = route('public-quotes.show', ['quoteUuid' => $quote->quote_uuid]);
        $unsubscribeUrl = $quote->client?->email
            ? url('/unsubscribe?email='.urlencode($quote->client->email))
            : null;

        SendQuoteEmailJob::dispatch(
            quoteId: $quote->id,
            to: (string) $request->string('to'),
            cc: $cc,
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
            'type' => $scheduleEnabled ? 'scheduled' : 'sent',
            'description' => $scheduleEnabled
                ? 'Quote delivery scheduled.'
                : 'Quote sent to client.',
            'metadata' => [
                'to' => (string) $request->string('to'),
                'cc' => $cc,
                'channel' => 'email',
                'scheduled_at' => $scheduleEnabled ? $sendAt->toISOString() : null,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Notification::send(
            $workspace->members()->get(),
            new QuoteSentInternalNotification(
                quote: $quote,
                scheduled: $scheduleEnabled,
                scheduledAt: $scheduleEnabled ? $sendAt->toDateTimeString() : null,
            ),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $scheduleEnabled
                ? __('Quote send scheduled.')
                : __('Quote sent successfully.'),
        ]);

        return back();
    }
}
