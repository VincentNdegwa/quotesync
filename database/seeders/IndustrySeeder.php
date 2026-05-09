<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            [
                'name' => 'Technology',
                'description' => 'Software, IT services, and technology companies',
                'icon' => 'code',
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Medical, healthcare, and pharmaceutical companies',
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
                'name' => 'Manufacturing',
                'description' => 'Manufacturing, production, and industrial companies',
                'icon' => 'factory',
                'color' => '#6366f1',
            ],
            [
                'name' => 'Construction',
                'description' => 'Construction, real estate, and property development',
                'icon' => 'building',
                'color' => '#8b5cf6',
            ],
            [
                'name' => 'Education',
                'description' => 'Education, training, and e-learning',
                'icon' => 'graduation-cap',
                'color' => '#06b6d4',
            ],
            [
                'name' => 'Consulting',
                'description' => 'Professional services, consulting, and advisory',
                'icon' => 'briefcase',
                'color' => '#ec4899',
            ],
            [
                'name' => 'Food & Beverage',
                'description' => 'Restaurants, food service, and hospitality',
                'icon' => 'utensils',
                'color' => '#f97316',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Logistics, transportation, and shipping',
                'icon' => 'truck',
                'color' => '#14b8a6',
            ],
            [
                'name' => 'Creative',
                'description' => 'Design, marketing, and creative agencies',
                'icon' => 'palette',
                'color' => '#a855f7',
            ],
            [
                'name' => 'Legal',
                'description' => 'Legal services, law firms, and compliance',
                'icon' => 'scale',
                'color' => '#64748b',
            ],
        ];

        foreach ($industries as $industry) {
            Industry::query()->firstOrCreate(
                ['name' => $industry['name']],
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
