<?php

namespace App\Enums;

enum Feature: string
{
    case MAX_USERS = 'max_users';
    case MAX_CLIENTS = 'max_clients';
    case MAX_CATALOG_ITEMS = 'max_catalog_items';
    case MAX_INVOICES_PER_MONTH = 'max_invoices_per_month';
    case MAX_QUOTES_PER_MONTH = 'max_quotes_per_month';
    case MAX_TEMPLATES = 'max_templates';
    case AI_CREDITS_PER_MONTH = 'ai_credits_per_month';
    case APPROVAL_RULES = 'approval_rules';
    case APPROVAL_WORKFLOWS = 'approval_workflows';
    case CUSTOM_DOMAINS = 'custom_domains';
    case FOLLOW_UP_SEQUENCES = 'follow_up_sequences';
    case WORKSPACES = 'workspaces';

    public function label(): string
    {
        return match ($this) {
            self::MAX_USERS => 'Users',
            self::MAX_CLIENTS => 'Clients',
            self::MAX_CATALOG_ITEMS => 'Catalog items',
            self::MAX_INVOICES_PER_MONTH => 'Invoices / month',
            self::MAX_QUOTES_PER_MONTH => 'Quotes / month',
            self::MAX_TEMPLATES => 'Templates',
            self::AI_CREDITS_PER_MONTH => 'AI credits / month',
            self::APPROVAL_RULES => 'Approval rules',
            self::APPROVAL_WORKFLOWS => 'Approval workflows',
            self::CUSTOM_DOMAINS => 'Custom domains',
            self::FOLLOW_UP_SEQUENCES => 'Follow-up sequences',
            self::WORKSPACES => 'Workspaces',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MAX_USERS => 'Users',
            self::MAX_CLIENTS => 'Users',
            self::MAX_CATALOG_ITEMS => 'Package',
            self::MAX_INVOICES_PER_MONTH => 'TrendingUp',
            self::MAX_QUOTES_PER_MONTH => 'FileText',
            self::MAX_TEMPLATES => 'Layout',
            self::AI_CREDITS_PER_MONTH => 'Sparkles',
            self::APPROVAL_RULES => 'CheckCircle',
            self::APPROVAL_WORKFLOWS => 'Shield',
            self::CUSTOM_DOMAINS => 'Globe',
            self::FOLLOW_UP_SEQUENCES => 'Zap',
            self::WORKSPACES => 'Building',
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::APPROVAL_WORKFLOWS => 'boolean',
            default => 'number',
        };
    }

    public static function all(): array
    {
        return array_map(fn ($case) => [
            'key' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
            'type' => $case->type(),
        ], self::cases());
    }

    public static function forFrontend(): array
    {
        return collect(self::cases())
            ->map(fn ($case) => [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'type' => $case->type(),
            ])
            ->values()
            ->toArray();
    }
}
