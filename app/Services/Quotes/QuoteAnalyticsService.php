<?php

namespace App\Services\Quotes;

use App\Enums\TrackingEventType;
use App\Models\Quote;
use App\Models\QuoteTrackingEvent;
use Illuminate\Support\Collection;

class QuoteAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(Quote $quote): array
    {
        $events = $quote->trackingEvents;

        return [
            'total_views' => $events->where('event_type', TrackingEventType::View->value)->count(),
            'unique_visitors' => $events->where('event_type', TrackingEventType::View->value)
                ->pluck('ip_address')
                ->unique()
                ->count(),
            'avg_time_spent_seconds' => $events->where('event_type', TrackingEventType::TimeSpent->value)
                ->avg('duration_seconds') ?? 0,
            'max_scroll_depth_percent' => $events->where('event_type', TrackingEventType::ScrollDepth->value)
                ->max('scroll_depth_percent') ?? 0,
            'section_views' => $this->aggregateSectionViews($events),
            'scroll_heatmap' => $this->generateScrollHeatmap($events),
            'timeline' => $this->generateTimeline($events),
        ];
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{section: string, count: int}>
     */
    private function aggregateSectionViews(Collection $events): array
    {
        return $events->where('event_type', TrackingEventType::SectionVisible->value)
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->map(fn (Collection $group): array => [
                'section' => $group->first()->section_name,
                'count' => $group->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->all();
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{depth: int, count: int}>
     */
    private function generateScrollHeatmap(Collection $events): array
    {
        $scrollEvents = $events->where('event_type', TrackingEventType::ScrollDepth->value);

        $buckets = [];
        for ($i = 0; $i <= 100; $i += 10) {
            $buckets[$i] = 0;
        }

        foreach ($scrollEvents as $event) {
            $depth = $event->scroll_depth_percent;
            $bucket = (int) floor($depth / 10) * 10;
            $buckets[$bucket] = ($buckets[$bucket] ?? 0) + 1;
        }

        return collect($buckets)
            ->map(fn (int $count, int $depth): array => [
                'depth' => $depth,
                'count' => $count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, QuoteTrackingEvent> $events
     * @return array<int, array{date: string, views: int}>
     */
    private function generateTimeline(Collection $events): array
    {
        return $events->where('event_type', TrackingEventType::View->value)
            ->groupBy(fn (QuoteTrackingEvent $event): string => $event->occurred_at->toDateString())
            ->map(fn (Collection $group, string $date): array => [
                'date' => $date,
                'views' => $group->count(),
            ])
            ->sortBy('date')
            ->values()
            ->all();
    }
}
