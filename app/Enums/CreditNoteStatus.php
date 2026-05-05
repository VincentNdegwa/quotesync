<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Voided => 'Voided',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Issued => 'default',
            self::Voided => 'outline',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Draft => 'text-muted-foreground',
            self::Issued => 'text-primary',
            self::Voided => 'text-orange-600',
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
            self::Draft => [self::Issued, self::Voided],
            self::Issued => [self::Voided],
            self::Voided => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public function availableActions(): array
    {
        return match ($this) {
            self::Draft => ['edit', 'delete', 'issue', 'void'],
            self::Issued => ['void'],
            self::Voided => [],
        };
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }
}
