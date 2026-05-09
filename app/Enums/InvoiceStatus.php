<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
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
            self::Sent => 'text-white',
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
            'badgeColor' => $status->badgeColor(),
            'cssColor' => $status->cssColor(),
            'availableActions' => $status->availableActions(),
        ], self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Paid, self::Void, self::Draft],
            self::Partial => [self::Paid, self::Void],
            self::Paid => [],
            self::Overdue => [self::Paid, self::Void],
            self::Void => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public function availableActions(): array
    {
        return match ($this) {
            self::Draft => ['edit', 'send', 'delete', 'duplicate', 'preview'],
            self::Sent => ['send', 'duplicate', 'preview'],
            self::Partial => ['duplicate', 'preview'],
            self::Paid => ['duplicate', 'preview', 'archive'],
            self::Overdue => ['duplicate', 'preview'],
            self::Void => ['duplicate', 'preview'],
        };
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }
}
