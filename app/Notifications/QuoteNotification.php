<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class QuoteNotification extends Notification
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Quote $quote) {}

    /**
     * Get notification channels based on workspace settings.
     *
     * @param  object  $notifiable
     * @param  string  $notificationKey  The key for the notification setting (e.g., 'notify_quote_viewed')
     * @param  string  $channelKey  The key for the channel setting (e.g., 'notify_quote_viewed_channel')
     * @return array<int, string>
     */
    protected function getChannelsFromSettings(object $notifiable, string $notificationKey, string $channelKey): array
    {
        $workspace = $this->quote->workspace;
        $settingsService = app(WorkspaceSettingsService::class);
        $settings = $settingsService->groupForFrontend($workspace, 'notifications')['fields'] ?? [];

        $enabled = $settings[$notificationKey]['value'] ?? true;
        if (! $enabled) {
            return [];
        }

        $channels = $settings[$channelKey]['value'] ?? ['in_app'];

        $result = [];
        if (in_array('in_app', $channels, true)) {
            $result[] = 'database';
        }
        if (in_array('mail', $channels, true)) {
            $result[] = 'mail';
        }

        return $result;
    }

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
