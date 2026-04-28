<?php

namespace App\Jobs;

use App\Enums\FollowUpChannel;
use App\Enums\QuoteActivityType;
use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Mail\QuoteFollowUpMail;
use App\Models\QuoteActivity;
use App\Models\QuoteFollowUp;
use App\Services\Quotes\QuotePlaceholderService;
use App\Notifications\QuoteFollowUpSentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendFollowUpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $quoteFollowUpId) {}

    public function handle(): void
    {
        $quoteFollowUp = QuoteFollowUp::query()
            ->with([
                'quote:id,workspace_id,quote_uuid,number,title,status,client_id,total,currency,valid_until,created_by,assigned_to',
                'quote.client:id,email,contact_name,company_name',
                'quote.shortCode:id,quote_id,code',
                'quote.workspace:id,name,display_name,logo_path',
                'step:id,follow_up_sequence_id,channel,subject,message_template',
            ])
            ->find($this->quoteFollowUpId);

        if (! $quoteFollowUp || $quoteFollowUp->status !== QuoteFollowUpStatus::Pending) {
            return;
        }

        $quote = $quoteFollowUp->quote;
        $step = $quoteFollowUp->step;

        if (! $quote || ! $step) {
            return;
        }

        if (in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::Declined], true)) {
            $quoteFollowUp->forceFill([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
            ])->save();

            return;
        }

        if ($step->channel !== FollowUpChannel::Email) {
            return;
        }

        $to = $quote->client?->email;

        if (! is_string($to) || $to === '') {
            $quoteFollowUp->forceFill([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
            ])->save();

            return;
        }

        $viewIdentifier = $quote->shortCode?->code ?: $quote->quote_uuid;
        $viewUrl = route('public-quotes.show', ['quoteUuid' => $viewIdentifier]);

        $workspace = $quote->workspace;
        $companyName = (string) ($workspace?->display_name ?: $workspace?->name ?: config('app.name'));
        $logoUrl = $workspace?->logo_path ? \Illuminate\Support\Facades\Storage::url($workspace->logo_path) : null;

        $subject = QuotePlaceholderService::replacePlaceholdersFromQuote(
            (string) ($step->subject ?: 'Follow-up for quote {quote_number}'),
            $quote,
            $workspace,
            null,
            $viewUrl
        );
        $message = QuotePlaceholderService::replacePlaceholdersFromQuote(
            $step->message_template,
            $quote,
            $workspace,
            null,
            $viewUrl
        );

        $unsubscribeUrl = null;

        Mail::to($to)->send(new QuoteFollowUpMail(
            subjectLine: $subject,
            messageBody: $message,
            companyName: $companyName,
            logoUrl: $logoUrl,
            viewUrl: $viewUrl,
            validUntil: $quote->valid_until?->toDateString(),
            unsubscribeUrl: $unsubscribeUrl,
        ));

        $quoteFollowUp->forceFill([
            'status' => QuoteFollowUpStatus::Sent->value,
            'sent_at' => now(),
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => QuoteActivityType::FollowUpSent->value,
            'description' => 'Automated follow-up sent to client.',
            'metadata' => [
                'quote_follow_up_id' => $quoteFollowUp->id,
                'channel' => $step->channel->value,
                'to' => $to,
            ],
            'ip_address' => null,
            'user_agent' => 'queue',
        ]);

        $recipients = collect([$quote->creator, $quote->assignee])
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new QuoteFollowUpSentNotification($quoteFollowUp));
        }
    }
}
