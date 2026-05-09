<?php

namespace App\Enums;

enum WinProbabilityConfidence: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::None => 'secondary',
            self::Low => 'outline',
            self::Medium => 'default',
            self::High => 'default',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::None => 'text-slate-500',
            self::Low => 'text-amber-600',
            self::Medium => 'text-blue-600',
            self::High => 'text-emerald-600',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function all(): array
    {
        return array_map(fn (self $confidence) => [
            'value' => $confidence->value,
            'label' => $confidence->label(),
            'badgeColor' => $confidence->badgeColor(),
            'cssColor' => $confidence->cssColor(),
        ], self::cases());
    }
}
