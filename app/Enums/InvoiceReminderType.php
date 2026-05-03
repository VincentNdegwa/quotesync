<?php

namespace App\Enums;

enum InvoiceReminderType: string
{
    case First = 'first';
    case Second = 'second';
    case Third = 'third';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::First => 'First Reminder',
            self::Second => 'Second Reminder',
            self::Third => 'Third Reminder',
            self::Final => 'Final Reminder',
        };
    }

    public static function all(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
