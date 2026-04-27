<?php

namespace App\Services\Quotes;

use App\Enums\TrackingEventType;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\QuoteTrackingEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuoteAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(Quote $quote): array
    {
        $quote->loadMissing(['trackingEvents', 'quoteFollowUps.step']);

        $events = $quote->trackingEvents;
        $viewEvents = $events->filter(fn (QuoteTrackingEvent $event): bool => $this->isEventType($event, TrackingEventType::View));
        $timeSpentEvents = $events->filter(fn (QuoteTrackingEvent $event): bool => $this->isEventType($event, TrackingEventType::TimeSpent));

        return [
            'opened_count' => $viewEvents->count(),
            'total_time_read_seconds' => $timeSpentEvents->sum('duration_seconds') ?? 0,
            'last_opened_at' => $viewEvents->max('occurred_at'),
            'device_breakdown' => $this->getDeviceBreakdown($viewEvents),
            'view_timeline' => $this->generateViewTimeline($viewEvents),
            'section_engagement' => $this->aggregateSectionEngagement($events),
            'follow_up_timeline' => $this->generateFollowUpTimeline($quote, $viewEvents),
        ];
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{device: string, count: int, percentage: float}>
     */
    private function getDeviceBreakdown(Collection $events): array
    {
        $total = $events->count();

        if ($total === 0) {
            return [];
        }

        return $events
            ->groupBy(function (QuoteTrackingEvent $event): string {
                return $this->detectDeviceType($event->user_agent);
            })
            ->map(function (Collection $group, string $device) use ($total): array {
                return [
                    'device' => $device,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $total) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{view_number: int, date: string, time: string, duration_seconds: int|null, device: string}>
     */
    private function generateViewTimeline(Collection $events): array
    {
        $sortedEvents = $events->sortBy('occurred_at')->values();
        $viewEvents = $sortedEvents
            ->filter(fn (QuoteTrackingEvent $event): bool => $this->isEventType($event, TrackingEventType::View))
            ->values();

        return $viewEvents
            ->map(function (QuoteTrackingEvent $viewEvent, int $index) use ($sortedEvents, $viewEvents): array {
                $nextViewAt = $viewEvents->get($index + 1)?->occurred_at;

                $durationSeconds = $sortedEvents
                    ->filter(function (QuoteTrackingEvent $event) use ($viewEvent, $nextViewAt): bool {
                        if (! $this->isEventType($event, TrackingEventType::TimeSpent)) {
                            return false;
                        }

                        if ($event->occurred_at->lessThan($viewEvent->occurred_at)) {
                            return false;
                        }

                        return $nextViewAt === null || $event->occurred_at->lessThan($nextViewAt);
                    })
                    ->sum('duration_seconds');

                return [
                    'view_number' => $index + 1,
                    'date' => $viewEvent->occurred_at->toDateString(),
                    'time' => $viewEvent->occurred_at->format('g:i A'),
                    'duration_seconds' => $durationSeconds > 0 ? (int) $durationSeconds : null,
                    'device' => $this->detectDeviceType($viewEvent->user_agent),
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{section: string, time_spent_seconds: int, count: int}>
     */
    private function aggregateSectionEngagement(Collection $events): array
    {
        return $events
            ->filter(fn (QuoteTrackingEvent $event): bool => $this->isEventType($event, TrackingEventType::SectionVisible))
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->map(function (Collection $group, string $section): array {
                $totalTime = $group->sum(function (QuoteTrackingEvent $event): int {
                    return (int) ($event->duration_seconds ?? 0);
                });

                return [
                    'section' => $section,
                    'time_spent_seconds' => $totalTime,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('time_spent_seconds')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $viewEvents
     * @return array<int, array{date: string, event: string, icon: string}>
     */
    private function generateFollowUpTimeline(Quote $quote, Collection $viewEvents): array
    {
        $timeline = [];

        $sentAt = $quote->sent_at ?? $quote->created_at;
        $timeline[] = [
            'date' => $sentAt->toDateString(),
            'event' => 'Quote sent',
            'icon' => 'send',
        ];

        $firstView = $viewEvents->sortBy('occurred_at')->first();
        if ($firstView instanceof QuoteTrackingEvent) {
            $timeline[] = [
                'date' => $firstView->occurred_at->toDateString(),
                'event' => 'First opened' . ($firstView->occurred_at->isSameDay($sentAt) ? ' (same day, fast)' : ''),
                'icon' => 'eye',
            ];
        }

        foreach ($quote->quoteFollowUps->sortBy(fn ($followUp) => $followUp->sent_at ?? $followUp->scheduled_at ?? $followUp->created_at) as $followUp) {
            if (! $followUp instanceof QuoteFollowUp) {
                continue;
            }

            $status = $followUp->status->value;
            $date = $followUp->sent_at ?? $followUp->scheduled_at ?? $followUp->created_at;

            if ($date === null) {
                continue;
            }

            $timeline[] = [
                'date' => $date->toDateString(),
                'event' => match ($status) {
                    'sent' => 'Follow-up sent' . ($followUp->step?->subject ? ' · ' . $followUp->step->subject : ''),
                    'pending' => 'Follow-up scheduled' . ($followUp->step?->subject ? ' · ' . $followUp->step->subject : ''),
                    'cancelled' => 'Follow-up cancelled' . ($followUp->step?->subject ? ' · ' . $followUp->step->subject : ''),
                    default => 'Follow-up updated',
                },
                'icon' => match ($status) {
                    'sent' => 'mail',
                    'pending' => 'clock',
                    'cancelled' => 'x-circle',
                    default => 'message-square',
                },
            ];
        }

        if ($quote->accepted_at) {
            $timeline[] = [
                'date' => $quote->accepted_at->toDateString(),
                'event' => 'Quote accepted',
                'icon' => 'check-circle',
            ];
        } elseif ($quote->declined_at) {
            $timeline[] = [
                'date' => $quote->declined_at->toDateString(),
                'event' => 'Quote declined',
                'icon' => 'x-circle',
            ];
        }

        return collect($timeline)
            ->sortBy('date')
            ->values()
            ->all();
    }

    private function detectDeviceType(?string $userAgent): string
    {
        $normalized = Str::lower($userAgent ?? '');

        if ($normalized === '') {
            return 'unknown';
        }

        if (Str::contains($normalized, ['ipad', 'tablet'])) {
            return 'tablet';
        }

        if (Str::contains($normalized, ['mobi', 'iphone', 'android'])) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function isEventType(QuoteTrackingEvent $event, TrackingEventType $type): bool
    {
        return $event->event_type instanceof TrackingEventType
            ? $event->event_type === $type
            : $event->event_type === $type->value;
    }
}
