<?php

namespace App\Enums;

enum RecurringInvoiceFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    public static function all(): array
    {
        return array_map(fn (self $frequency) => [
            'value' => $frequency->value,
            'label' => $frequency->label(),
        ], self::cases());
    }
}
