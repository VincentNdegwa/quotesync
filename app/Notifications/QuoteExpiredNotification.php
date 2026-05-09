<?php

namespace App\Notifications;

use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class QuoteExpiredNotification extends QuoteNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $workspace = $this->quote->workspace;
        $settingsService = app(WorkspaceSettingsService::class);
        $settings = $settingsService->groupForFrontend($workspace, 'notifications')['fields'] ?? [];

        $enabled = $settings['notify_quote_expired']['value'] ?? true;
        if (! $enabled) {
            return [];
        }

        // Quote expired only has in_app by default (no channel setting in config)
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload([
            'kind' => 'quote.expired',
            'icon' => 'clock-3',
            'title' => $this->quoteLabel().' expired',
            'message' => $this->quote->valid_until
                ? 'Expired on '.$this->quote->valid_until->toFormattedDateString().'.'
                : 'The quote expired.',
        ]);
    }
}
