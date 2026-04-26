<?php

namespace App\Enums;

enum QuoteApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Approved => 'default',
            self::Rejected => 'destructive',
        };
    }
}
