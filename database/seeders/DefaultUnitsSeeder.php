<?php

namespace Database\Seeders;

use App\Models\ConfigurationUnit;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DefaultUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUnits = [
            ['name' => 'Hour', 'symbol' => 'hr'],
            ['name' => 'Day', 'symbol' => 'day'],
            ['name' => 'Unit', 'symbol' => 'unit'],
            ['name' => 'Square Meter', 'symbol' => 'sqm'],
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Meter', 'symbol' => 'm'],
            ['name' => 'Lot', 'symbol' => 'lot'],
            ['name' => 'Month', 'symbol' => 'month'],
        ];

        Workspace::chunk(100, function ($workspaces) use ($defaultUnits) {
            foreach ($workspaces as $workspace) {
                foreach ($defaultUnits as $unitData) {
                    ConfigurationUnit::query()->firstOrCreate(
                        [
                            'workspace_id' => $workspace->id,
                            'name' => $unitData['name'],
                        ],
                        [
                            'symbol' => $unitData['symbol'],
                            'is_active' => true,
                            'created_by' => $workspace->owner_id ?? null,
                        ]
                    );
                }
            }
        });
    }
}
