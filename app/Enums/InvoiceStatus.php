<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Void => 'Void',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'default',
            self::Viewed => 'default',
            self::Partial => 'default',
            self::Paid => 'default',
            self::Overdue => 'destructive',
            self::Void => 'secondary',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Draft => 'text-muted-foreground',
            self::Sent => 'text-primary',
            self::Viewed => 'text-primary',
            self::Partial => 'text-primary',
            self::Paid => 'text-green-600',
            self::Overdue => 'text-red-600',
            self::Void => 'text-muted-foreground',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function all(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
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
