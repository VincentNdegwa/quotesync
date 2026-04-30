<?php

namespace App\Services\Quotes;

use App\Jobs\SendQuoteEmailJob;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use App\Notifications\QuoteSentInternalNotification;
use App\Services\Pdf\QuotePdfService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class QuoteSendingService
{
    public function __construct(
        private WorkspaceSettingsService $workspaceSettingsService,
        private QuoteShortCodeService $quoteShortCodeService,
        private QuoteFollowUpSchedulerService $quoteFollowUpSchedulerService,
    ) {}

    public function sendQuote(
        Quote $quote,
        Workspace $workspace,
        ?int $userId = null,
        bool $attachPdf = false,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $quote->loadMissing(['client', 'sections.lineItems']);

        $to = $quote->client?->email;

        if (empty($to)) {
            return;
        }

        $emailFields = collect($this->workspaceSettingsService->groupForFrontend($workspace, 'email')['fields'] ?? [])->keyBy('key');
        $brandFields = collect($this->workspaceSettingsService->groupForFrontend($workspace, 'brand')['fields'] ?? [])->keyBy('key');

        $companyName = (string) ($brandFields->get('company_name')['value'] ?? config('app.name'));
        $logoPath = $brandFields->get('logo_path')['value'] ?? null;
        $logoUrl = is_string($logoPath) && $logoPath !== '' ? Storage::url($logoPath) : null;

        $subjectTemplate = $emailFields->get('quote_email_subject')['value'] ?? 'Your Quote {quote_number} from {company_name}';
        $bodyTemplate = $emailFields->get('quote_email_template')['value'] ?? "Hi {client_name},\n\nPlease review quote {quote_number} totaling {quote_total}.";

        $sendAt = now();
        $shortCode = $this->quoteShortCodeService->getOrCreateCode($quote);
        $viewUrl = route('public-quotes.show', ['quoteUuid' => $shortCode]);
        $unsubscribeUrl = url('/unsubscribe?email='.urlencode($to));

        $user = $userId ? $quote->creator : null;

        $subjectLine = QuotePlaceholderService::replacePlaceholdersFromQuote(
            $subjectTemplate,
            $quote,
            $workspace,
            $user,
            $viewUrl
        );
        $messageBody = QuotePlaceholderService::replacePlaceholdersFromQuote(
            $bodyTemplate,
            $quote,
            $workspace,
            $user,
            $viewUrl
        );

        $pdfPath = null;

        if ($attachPdf) {
            if (! $quote->pdf_path) {
                $pdfService = app(QuotePdfService::class);
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

        $this->quoteFollowUpSchedulerService->scheduleDefaultSequence($quote, $workspace, $sendAt);

        $metadata = [
            'to' => $to,
            'cc' => [],
            'channel' => 'email',
            'scheduled_at' => null,
        ];

        if ($ipAddress || $userAgent) {
            $metadata['ip_address'] = $ipAddress;
            $metadata['user_agent'] = $userAgent;
        }

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => 'sent',
            'description' => 'Quote sent to client.',
            'metadata' => $metadata,
        ]);

        Notification::send(
            $workspace->members()->get(),
            new QuoteSentInternalNotification(
                quote: $quote,
                scheduled: false,
                scheduledAt: null,
            ),
        );
    }
}
