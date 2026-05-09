<?php

namespace App\Enums;

enum PricingType: string
{
    case FixedPrice = 'fixed_price';
    case DiscountPercent = 'discount_percent';

    public function label(): string
    {
        return match ($this) {
            self::FixedPrice => 'Fixed Price',
            self::DiscountPercent => 'Percentage Off',
        };
    }
}
