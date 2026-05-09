<?php

namespace App\Enums;

enum TrackingEventType: string
{
    case View = 'view';
    case SectionVisible = 'section_visible';
    case ScrollDepth = 'scroll_depth';
    case TimeSpent = 'time_spent';
    case LinkClick = 'link_click';

    public function label(): string
    {
        return match ($this) {
            self::View => 'View',
            self::SectionVisible => 'Section Visible',
            self::ScrollDepth => 'Scroll Depth',
            self::TimeSpent => 'Time Spent',
            self::LinkClick => 'Link Click',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function all(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
