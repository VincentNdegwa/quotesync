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
    case PendingApproval = 'pending_approval';

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
            self::PendingApproval => 'Pending Approval',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'outline',
            self::Viewed => 'outline',
            self::Accepted => 'outline',
            self::Declined => 'default',
            self::Won => 'default',
            self::Lost => 'default',
            self::Expired => 'secondary',
            self::PendingApproval => 'outline',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Draft => 'border-slate-400 text-slate-600',
            self::Sent => 'border-blue-500 text-blue-600',
            self::Viewed => 'border-cyan-500 text-cyan-600',
            self::Accepted => 'border-emerald-500 text-emerald-600',
            self::Won => 'bg-emerald-500 hover:bg-emerald-600 text-white border-emerald-600',
            self::Declined => 'border-rose-500 text-rose-600',
            self::Lost => 'border-orange-500 text-orange-600',
            self::Expired => 'border-amber-400 text-amber-700',
            self::PendingApproval => 'border-amber-500 text-amber-600',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Draft => '#94a3b8',
            self::Sent => '#3b82f6',
            self::Viewed => '#06b6d4',
            self::Accepted => '#10b981',
            self::Won => '#10b981',
            self::Declined => '#f43f5e',
            self::Lost => '#f97316',
            self::Expired => '#fbbf24',
            self::PendingApproval => '#f59e0b',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function pipelineStatuses(): array
    {
        return [
            self::Sent->value,
            self::Viewed->value,
            self::PendingApproval->value,
        ];
    }

    public static function sentStatuses(): array
    {
        return [
            self::Sent->value,
            self::Viewed->value,
            self::Accepted->value,
            self::Won->value,
            self::Lost->value,
            self::Expired->value,
            self::Declined->value,
        ];
    }

    public static function closedWonStatuses(): array
    {
        return [
            self::Won->value,
            self::Accepted->value,
        ];
    }

    public static function closedLostStatuses(): array
    {
        return [
            self::Lost->value,
            self::Declined->value,
        ];
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
            self::Draft => [self::Sent, self::PendingApproval],
            self::Sent => [self::Viewed, self::Won, self::Lost, self::Expired, self::Draft],
            self::Viewed => [self::Accepted, self::Declined, self::Won, self::Lost, self::Expired, self::Draft],
            self::Accepted => [self::Won, self::Lost],
            self::Declined => [self::Lost, self::Draft],
            self::Won => [],
            self::Lost => [],
            self::Expired => [self::Draft],
            self::PendingApproval => [self::Sent, self::Draft],
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
            self::PendingApproval => ['duplicate', 'preview'],
        };
    }
}
