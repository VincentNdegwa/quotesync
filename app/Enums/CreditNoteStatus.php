<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Applied = 'applied';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Applied => 'Applied',
        };
    }

    public static function all(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
