<?php

namespace App\Enums;

enum InvoiceReminderType: string
{
    case BeforeDue = 'before_due';
    case OnDue = 'on_due';
    case AfterDue = 'after_due';

    public function label(): string
    {
        return match ($this) {
            self::BeforeDue => 'Before Due Date',
            self::OnDue => 'On Due Date',
            self::AfterDue => 'After Due Date',
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
