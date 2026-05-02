<?php

namespace App\Enums;

enum InvoiceActivityType: string
{
    case Created = 'created';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Partial = 'partial';
    case Voided = 'voided';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Partial => 'Partial',
            self::Voided => 'Voided',
            self::Scheduled => 'Scheduled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'text-muted-foreground bg-muted',
            self::Sent => 'text-primary bg-primary/10',
            self::Viewed => 'text-secondary bg-secondary/10',
            self::Paid => 'text-emerald-600 bg-emerald-100',
            self::Overdue => 'text-red-600 bg-red-100',
            self::Partial => 'text-amber-600 bg-amber-100',
            self::Voided => 'text-slate-600 bg-slate-200',
            self::Scheduled => 'text-cyan-600 bg-cyan-100',
        };
    }

    public static function all(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
