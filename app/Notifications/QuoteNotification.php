<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class QuoteNotification extends Notification
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Quote $quote) {}

    protected function quoteLabel(): string
    {
        if (filled($this->quote->number)) {
            return sprintf('Quote %s', $this->quote->number);
        }

        return sprintf('Quote "%s"', $this->quote->title);
    }

    protected function quoteUrl(): string
    {
        return route('quotes.show', $this->quote);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function payload(array $payload = []): array
    {
        return [
            'quote_id' => $this->quote->id,
            'quote_uuid' => $this->quote->quote_uuid,
            'quote_number' => $this->quote->number,
            'quote_title' => $this->quote->title,
            'quote_status' => $this->quote->status,
            'url' => $this->quoteUrl(),
            ...$payload,
        ];
    }
}
