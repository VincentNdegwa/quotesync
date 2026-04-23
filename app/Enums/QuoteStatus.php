<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Won = 'won';
    case Lost = 'lost';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Expired => 'Expired',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'outline',
            self::Viewed => 'outline',
            self::Accepted => 'outline',
            self::Declined => 'destructive',
            self::Won => 'default',
            self::Lost => 'destructive',
            self::Expired => 'secondary',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Draft => '',
            self::Sent => 'border-primary text-primary',
            self::Viewed => 'border-secondary text-secondary',
            self::Accepted => 'border-primary text-primary',
            self::Declined => 'border-destructive text-destructive',
            self::Won => 'bg-primary hover:bg-primary text-primary-foreground',
            self::Lost => 'border-destructive text-destructive',
            self::Expired => 'border-muted text-muted-foreground',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function all(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'badgeColor' => $status->badgeColor(),
            'cssColor' => $status->cssColor(),
        ], self::cases());
    }
}