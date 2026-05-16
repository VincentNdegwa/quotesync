<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'Acquisition tool for freelancers and solo professionals',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'paddle_monthly_price_id' => null,
                'paddle_yearly_price_id' => null,
                'features' => [
                    'max_users' => 1,
                    'max_quotes_per_month' => 5,
                    'max_invoices_per_month' => 3,
                    'max_catalog_items' => 10,
                    'max_templates' => 1,
                    'max_clients' => 25,
                    'ai_credits_per_month' => 0,
                    'follow_up_sequences' => 0,
                    'approval_workflows' => false,
                    'custom_domains' => 0,
                    'workspaces' => 1,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'description' => 'Main revenue driver for individual businesses',
                'monthly_price' => 29.00,
                'yearly_price' => 276.00,
                'paddle_monthly_price_id' => 'pri_growth_monthly',
                'paddle_yearly_price_id' => 'pri_growth_yearly',
                'features' => [
                    'max_users' => 1,
                    'max_quotes_per_month' => null,
                    'max_invoices_per_month' => null,
                    'max_catalog_items' => null,
                    'max_templates' => null,
                    'max_clients' => null,
                    'ai_credits_per_month' => 100,
                    'follow_up_sequences' => 3,
                    'approval_workflows' => false,
                    'custom_domains' => 1,
                    'workspaces' => 1,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'team',
                'name' => 'Team',
                'description' => 'Growing businesses with multiple team members',
                'monthly_price' => 79.00,
                'yearly_price' => 756.00,
                'paddle_monthly_price_id' => 'pri_team_monthly',
                'paddle_yearly_price_id' => 'pri_team_yearly',
                'features' => [
                    'max_users' => 10,
                    'max_quotes_per_month' => null,
                    'max_invoices_per_month' => null,
                    'max_catalog_items' => null,
                    'max_templates' => null,
                    'max_clients' => null,
                    'ai_credits_per_month' => 500,
                    'follow_up_sequences' => null,
                    'approval_workflows' => true,
                    'approval_rules' => 5,
                    'custom_domains' => 1,
                    'workspaces' => 1,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'agency',
                'name' => 'Agency',
                'description' => 'Agencies managing multiple client workspaces',
                'monthly_price' => 199.00,
                'yearly_price' => 1908.00,
                'paddle_monthly_price_id' => 'pri_agency_monthly',
                'paddle_yearly_price_id' => 'pri_agency_yearly',
                'features' => [
                    'max_users' => null,
                    'max_quotes_per_month' => null,
                    'max_invoices_per_month' => null,
                    'max_catalog_items' => null,
                    'max_templates' => null,
                    'max_clients' => null,
                    'ai_credits_per_month' => 2000,
                    'follow_up_sequences' => null,
                    'approval_workflows' => true,
                    'approval_rules' => null,
                    'custom_domains' => null,
                    'workspaces' => null,
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
