<?php

namespace App\Notifications;

use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class QuoteViewedNotification extends QuoteNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($this->shouldThrottle($notifiable)) {
            return [];
        }

        return $this->getChannelsFromSettings($notifiable, 'notify_quote_viewed', 'notify_quote_viewed_channel');
    }

    /**
     * Check if notification should be throttled.
     */
    private function shouldThrottle(object $notifiable): bool
    {
        $workspace = $this->quote->workspace;
        $settingsService = app(WorkspaceSettingsService::class);
        $settings = $settingsService->groupForFrontend($workspace, 'notifications')['fields'] ?? [];

        $throttleMinutes = $settings['viewed_notify_throttle_minutes']['value'] ?? 60;

        if ($throttleMinutes <= 0) {
            return false;
        }

        $lastViewedNotification = $notifiable->notifications()
            ->where('data->kind', 'quote.viewed')
            ->where('data->quote_id', $this->quote->id)
            ->where('created_at', '>=', now()->subMinutes($throttleMinutes))
            ->exists();

        return $lastViewedNotification;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.viewed',
            'icon' => 'eye',
            'title' => $this->quoteLabel().' was viewed',
            'message' => 'The client opened the quote.',
        ]);
    }
}
