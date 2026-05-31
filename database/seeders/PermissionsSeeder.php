<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'dashboard.view',
        'analytics.view',
        'workspaces.switch',
        'team.view',
        'team.invitations.create',
        'team.invitations.delete',
        'profile.view',
        'profile.update',
        'profile.delete',
        'security.view',
        'security.password.update',
        'appearance.view',
        'approvals.manage',
        'approval_rules.create',
        'approval_rules.update',
        'approval_rules.delete',
        'quote_approvals.approve',
        'quote_approvals.reject',
        'clients.manage',
        'clients.create',
        'clients.update',
        'clients.delete',
        'clients.export',
        'clients.import',
        'clients.portal_invite',
        'client_contacts.manage',
        'client_contacts.create',
        'client_contacts.update',
        'client_contacts.delete',
        'catalog.manage',
        'catalog.create',
        'catalog.update',
        'catalog.delete',
        'catalog.import',
        'catalog.export',
        'catalog_variants.create',
        'catalog_variants.update',
        'catalog_variants.delete',
        'catalog_price_tiers.create',
        'catalog_price_tiers.update',
        'catalog_price_tiers.delete',
        'quotes.manage',
        'quotes.create',
        'quotes.update',
        'quotes.delete',
        'quotes.send',
        'quotes.status.update',
        'quotes.export',
        'quotes.pdf.generate',
        'quotes.pdf.download',
        'quotes.convert_to_invoice',
        'quotes.duplicate',
        'quotes.revise',
        'quotes.versions.restore',
        'quotes.reopen',
        'quotes.archive',
        'quotes.followups.cancel',
        'quotes.followups.send_now',
        'quotes.handover',
        'quote_messages.manage',
        'quote_messages.create',
        'quote_templates.manage',
        'quote_templates.create',
        'quote_templates.update',
        'quote_templates.delete',
        'invoices.manage',
        'invoices.create',
        'invoices.update',
        'invoices.delete',
        'invoices.send',
        'invoices.status.update',
        'invoices.export',
        'invoices.pdf.generate',
        'invoices.pdf.download',
        'invoices.duplicate',
        'invoices.archive',
        'invoice_payments.create',
        'invoice_payments.refund',
        'credit_notes.manage',
        'credit_notes.create',
        'credit_notes.update',
        'credit_notes.issue',
        'credit_notes.apply',
        'credit_notes.void',
        'tasks.manage',
        'tasks.create',
        'tasks.update',
        'tasks.delete',
        'comments.manage',
        'comments.create',
        'comments.delete',
        'notifications.read',
        'notifications.read_all',
        'settings.manage',
        'taxes.manage',
        'taxes.create',
        'taxes.update',
        'taxes.delete',
        'catalog_categories.manage',
        'catalog_categories.create',
        'catalog_categories.update',
        'catalog_categories.delete',
        'tags.manage',
        'tags.create',
        'tags.update',
        'tags.delete',
        'units.manage',
        'units.create',
        'units.update',
        'units.delete',
        'industries.manage',
        'industries.create',
        'industries.update',
        'industries.delete',
        'follow_up_sequences.manage',
        'follow_up_sequences.create',
        'follow_up_sequences.update',
        'follow_up_sequences.delete',
        'invoice_reminder_sequences.manage',
        'invoice_reminder_sequences.create',
        'invoice_reminder_sequences.update',
        'invoice_reminder_sequences.delete',
        'task_statuses.manage',
        'task_statuses.create',
        'task_statuses.update',
        'task_statuses.delete',
        'custom_domains.manage',
        'custom_domains.create',
        'custom_domains.update',
        'custom_domains.delete',
        'billing.manage',
        'billing.plans.view',
        'billing.subscribe',
        'billing.subscription.swap',
        'billing.subscription.cancel',
        'billing.subscription.resume',
        'billing.payment_method.update',
        'ai.use',
    ];

    public function run(): void
    {
        $permissionNames = collect(self::PERMISSIONS)->unique()->values();

        $now = now();

        $permissions = $permissionNames
            ->map(fn (string $name): array => [
                'name' => $name,
                'display_name' => Str::headline(str_replace(['.', '_', '-'], ' ', $name)),
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        Permission::query()->upsert($permissions, ['name'], ['display_name', 'description', 'updated_at']);

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin', 'workspace_id' => null],
            [
                'display_name' => 'Admin',
                'description' => 'Default admin role for newly registered users.',
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->all();

        $adminRole->permissions()->sync($permissionIds);
    }
}
