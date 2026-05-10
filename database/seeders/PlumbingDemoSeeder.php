<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlumbingDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create workspace
        $workspace = Workspace::firstOrCreate(
            ['name' => 'Plumbing Services Demo'],
            [
                'currency' => 'USD',
            ]
        );

        // Create user
        $user = User::firstOrCreate(
            ['email' => 'plumber@quotesync.com'],
            [
                'name' => 'John Plumber',
                'password' => Hash::make('password'),
            ]
        );

        $user->current_workspace_id = $workspace->id;
        $user->save();

        // Set user as workspace owner
        $workspace->owner_id = $user->id;
        $workspace->save();

        // Add user to workspace with owner role
        $workspace->users()->attach($user->id, ['role_id' => 1]); // Assuming role_id 1 is owner

        // Create plumbing category
        $category = CatalogCategory::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'name' => 'Plumbing Services',
            ],
            [
                'created_by' => $user->id,
                'is_active' => true,
            ]
        );

        // Create real plumbing catalog items
        $plumbingItems = [
            [
                'name' => 'Bathroom Installation',
                'description' => 'Complete bathroom fixture installation including toilet, sink, and shower',
                'sku' => 'PL-001',
                'unit_price' => 2500.00,
                'cost_price' => 1800.00,
            ],
            [
                'name' => 'Kitchen Sink Installation',
                'description' => 'Professional kitchen sink and faucet installation with plumbing connections',
                'sku' => 'PL-002',
                'unit_price' => 450.00,
                'cost_price' => 300.00,
            ],
            [
                'name' => 'Water Heater Installation',
                'description' => 'Tankless or traditional water heater installation with all necessary connections',
                'sku' => 'PL-003',
                'unit_price' => 1200.00,
                'cost_price' => 800.00,
            ],
            [
                'name' => 'Pipe Repair',
                'description' => 'Leak detection and pipe repair for residential and commercial properties',
                'sku' => 'PL-004',
                'unit_price' => 350.00,
                'cost_price' => 200.00,
            ],
            [
                'name' => 'Drain Cleaning',
                'description' => 'Professional drain cleaning and unclogging services',
                'sku' => 'PL-005',
                'unit_price' => 200.00,
                'cost_price' => 100.00,
            ],
            [
                'name' => 'Sewer Line Repair',
                'description' => 'Sewer line inspection, repair, and replacement services',
                'sku' => 'PL-006',
                'unit_price' => 3500.00,
                'cost_price' => 2500.00,
            ],
            [
                'name' => 'Garbage Disposal Installation',
                'description' => 'New garbage disposal unit installation and electrical connections',
                'sku' => 'PL-007',
                'unit_price' => 400.00,
                'cost_price' => 250.00,
            ],
            [
                'name' => 'Toilet Repair',
                'description' => 'Toilet repair and replacement services including all plumbing connections',
                'sku' => 'PL-008',
                'unit_price' => 275.00,
                'cost_price' => 175.00,
            ],
        ];

        foreach ($plumbingItems as $item) {
            CatalogItem::firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'sku' => $item['sku'],
                ],
                [
                    'created_by' => $user->id,
                    'catalog_category_id' => $category->id,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'is_active' => true,
                    'usage_count' => 0,
                ]
            );
        }

        $this->command->info('Plumbing demo data seeded successfully!');
        $this->command->info('Workspace: Plumbing Services Demo');
        $this->command->info('User: plumber@quotesync.com / password');
        $this->command->info('Catalog Items: 8 plumbing services created');
        $this->command->info('You can now log in and create quotes manually.');
    }
}
