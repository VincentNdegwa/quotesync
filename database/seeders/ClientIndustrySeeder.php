<?php

namespace Database\Seeders;

use App\Models\ClientIndustry;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ClientIndustrySeeder extends Seeder
{
    public function run(): void
    {
        Workspace::query()->each(function (Workspace $workspace) {
            $this->seedForWorkspace($workspace);
        });
    }

    public function seedForWorkspace(Workspace $workspace): void
    {
        $defaultIndustries = [
            [
                'name' => 'Technology',
                'description' => 'Software, IT services, and technology',
                'icon' => 'code',
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Medical, healthcare, and pharmaceutical',
                'icon' => 'stethoscope',
                'color' => '#ef4444',
            ],
            [
                'name' => 'Finance',
                'description' => 'Banking, financial services, and insurance',
                'icon' => 'bank',
                'color' => '#10b981',
            ],
            [
                'name' => 'Retail',
                'description' => 'Retail, e-commerce, and consumer goods',
                'icon' => 'shopping-bag',
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Consulting',
                'description' => 'Professional services, consulting, and advisory',
                'icon' => 'briefcase',
                'color' => '#ec4899',
            ],
        ];

        foreach ($defaultIndustries as $industry) {
            ClientIndustry::query()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'name' => $industry['name'],
                ],
                [
                    'description' => $industry['description'],
                    'icon' => $industry['icon'],
                    'color' => $industry['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
