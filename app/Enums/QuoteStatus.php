<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Won = 'won';
    case Lost = 'lost';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Expired => 'Expired',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'outline',
            self::Viewed => 'outline',
            self::Accepted => 'outline',
            self::Declined => 'destructive',
            self::Won => 'default',
            self::Lost => 'destructive',
            self::Expired => 'secondary',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Draft => '',
            self::Sent => 'border-primary text-primary',
            self::Viewed => 'border-secondary text-secondary',
            self::Accepted => 'border-primary text-primary',
            self::Declined => 'border-destructive text-white',
            self::Won => 'bg-primary hover:bg-primary text-primary-foreground',
            self::Lost => 'border-destructive text-white',
            self::Expired => 'border-muted text-muted-foreground',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function all(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'badgeColor' => $status->badgeColor(),
            'cssColor' => $status->cssColor(),
            'availableActions' => $status->availableActions(),
        ], self::cases());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Viewed, self::Won, self::Lost, self::Expired, self::Draft],
            self::Viewed => [self::Accepted, self::Declined, self::Won, self::Lost, self::Expired, self::Draft],
            self::Accepted => [self::Won, self::Lost],
            self::Declined => [self::Lost, self::Draft],
            self::Won => [],
            self::Lost => [],
            self::Expired => [self::Draft],
        };
    }

    public function canBeChangedManually(): bool
    {
        return match ($this) {
            self::Viewed,
            self::Accepted,
            self::Declined,
            self::Expired => false,
            default => true,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Won, self::Lost]);
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeSent(): bool
    {
        return in_array($this, [self::Draft, self::Expired]);
    }

    public function canBeResent(): bool
    {
        return in_array($this, [self::Sent, self::Viewed, self::Expired]);
    }

    public function canBeDeleted(): bool
    {
        return $this === self::Draft;
    }

    public function canBeArchived(): bool
    {
        return in_array($this, [self::Won, self::Lost]);
    }

    public function canBeReopened(): bool
    {
        return $this === self::Expired;
    }

    public function canBeRevised(): bool
    {
        return in_array($this, [self::Sent, self::Viewed, self::Declined, self::Lost]);
    }

    public function availableActions(): array
    {
        return match ($this) {
            self::Draft => ['edit', 'send', 'delete', 'duplicate', 'preview'],
            self::Sent => ['resend', 'mark_won', 'mark_lost', 'revise', 'duplicate', 'preview'],
            self::Viewed => ['resend', 'mark_won', 'mark_lost', 'revise', 'duplicate', 'preview'],
            self::Accepted => ['mark_won', 'mark_lost', 'duplicate', 'preview'],
            self::Declined => ['mark_lost', 'revise', 'duplicate', 'preview'],
            self::Won => ['archive', 'duplicate', 'preview', 'convert_to_invoice'],
            self::Lost => ['archive', 'revise', 'duplicate', 'preview'],
            self::Expired => ['reopen', 'duplicate', 'preview'],
        };
    }
}
