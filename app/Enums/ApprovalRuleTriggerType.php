<?php

namespace App\Enums;

enum ApprovalRuleTriggerType: string
{
    case ValueAbove = 'value_above';
    case ValueBelow = 'value_below';
    case Client = 'client';
    case AllQuotes = 'all_quotes';

    public function label(): string
    {
        return match ($this) {
            self::ValueAbove => 'Value Above',
            self::ValueBelow => 'Value Below',
            self::Client => 'Specific Client',
            self::AllQuotes => 'All Quotes',
        };
    }
}
