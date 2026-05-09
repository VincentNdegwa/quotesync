<?php

namespace App\Enums;

enum QuoteActivityType: string
{
    case Created = 'created';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case FollowUpSent = 'follow_up_sent';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case ApprovalRequested = 'approval_requested';
    case ApprovalApproved = 'approval_approved';
    case ApprovalRejected = 'approval_rejected';
    case ApprovalGranted = 'approval_granted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::FollowUpSent => 'Follow-up sent',
            self::Scheduled => 'Scheduled',
            self::Expired => 'Expired',
            self::ApprovalRequested => 'Approval requested',
            self::ApprovalApproved => 'Approval approved',
            self::ApprovalRejected => 'Approval rejected',
            self::ApprovalGranted => 'Approval granted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'text-primary bg-primary/10',
            self::Sent => 'text-primary bg-primary/10',
            self::Viewed => 'text-secondary bg-secondary/10',
            self::Accepted => 'text-primary bg-primary/10',
            self::Declined => 'text-destructive bg-destructive/10',
            self::FollowUpSent => 'text-destructive bg-destructive/10',
            self::Scheduled => 'text-destructive bg-destructive/10',
            self::ApprovalRequested => 'text-amber-600 bg-amber-100',
            self::ApprovalApproved => 'text-emerald-600 bg-emerald-100',
            self::ApprovalRejected => 'text-destructive bg-destructive/10',
            self::ApprovalGranted => 'text-emerald-600 bg-emerald-100',
            self::Expired => 'text-slate-600 bg-slate-200',
        };
    }

    public static function all(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'color' => $type->color(),
        ], self::cases());
    }
}
