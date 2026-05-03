<?php

namespace App\Enums;

enum CreditNoteType: string
{
    case Full = 'full';
    case Partial = 'partial';
    case LineItem = 'line_item';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full Invoice',
            self::Partial => 'Partial Amount',
            self::LineItem => 'Line Items',
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
