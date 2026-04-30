<?php

namespace App\Enums;

enum SignalDirection: string
{
    case Positive = 'positive';
    case Negative = 'negative';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positive',
            self::Negative => 'Negative',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Positive => 'default',
            self::Negative => 'secondary',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Positive => 'text-emerald-600',
            self::Negative => 'text-rose-600',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function all(): array
    {
        return array_map(fn (self $direction) => [
            'value' => $direction->value,
            'label' => $direction->label(),
            'badgeColor' => $direction->badgeColor(),
            'cssColor' => $direction->cssColor(),
        ], self::cases());
    }
}
