<?php

namespace App\Http\Controllers;

use App\Jobs\SendQuoteEmailJob;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Enums\QuoteStatus;
use App\Models\Workspace;
use App\Notifications\QuoteSentInternalNotification;
use App\Services\Quotes\QuoteFollowUpSchedulerService;
use App\Services\Quotes\QuotePlaceholderService;
use App\Services\Quotes\QuoteShortCodeService;
use App\Services\Pdf\QuotePdfService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QuoteSendController extends Controller
{
    public function store(
        Request $request,
        Quote $quote,
        WorkspaceSettingsService $workspaceSettingsService,
        QuoteShortCodeService $quoteShortCodeService,
        QuoteFollowUpSchedulerService $quoteFollowUpSchedulerService,
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

        $sendAt = now();
        $shortCode = $quoteShortCodeService->getOrCreateCode($quote);
        $viewUrl = route('public-quotes.show', ['quoteUuid' => $shortCode]);
        $unsubscribeUrl = url('/unsubscribe?email='.urlencode($to));

        $subjectLine = QuotePlaceholderService::replacePlaceholdersFromQuote(
            $subjectTemplate,
            $quote,
            $workspace,
            $request->user(),
            $viewUrl
        );
        $messageBody = QuotePlaceholderService::replacePlaceholdersFromQuote(
            $bodyTemplate,
            $quote,
            $workspace,
            $request->user(),
            $viewUrl
        );

        $attachPdf = $request->boolean('attach_pdf', false);
        $pdfPath = null;

        if ($attachPdf) {
            if (!$quote->pdf_path) {
                $pdfService = app(\App\Services\Pdf\QuotePdfService::class);
                $pdfPath = $pdfService->generate($quote);
                $quote->pdf_path = $pdfPath;
                $quote->save();
            } else {
                $pdfPath = $quote->pdf_path;
            }
        }

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
            pdfPath: $pdfPath,
        )->delay($sendAt);

        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $sendAt,
        ])->save();

        $quoteFollowUpSchedulerService->scheduleDefaultSequence($quote, $workspace, $sendAt);

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
