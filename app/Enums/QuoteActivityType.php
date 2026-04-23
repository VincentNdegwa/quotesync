<?php

namespace App\Enums;

enum QuoteActivityType: string
{
    case Created = 'created';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case FollowUpSent = 'follow_up_sent';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::FollowUpSent => 'Follow-up sent',
            self::Scheduled => 'Scheduled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'text-primary bg-primary/10',
            self::Sent => 'text-primary bg-primary/10',
            self::Viewed => 'text-secondary bg-secondary/10',
            self::Accepted => 'text-primary bg-primary/10',
            self::Declined => 'text-destructive bg-destructive/10',
            self::FollowUpSent => 'text-destructive bg-destructive/10',
            self::Scheduled => 'text-destructive bg-destructive/10',
        };
    }

    public static function all(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'color' => $type->color(),
        ], self::cases());
    }
}
