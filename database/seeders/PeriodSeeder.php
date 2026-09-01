<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        Period::create([
            'number' => 1,
            'name' => 'Jam ke-1',
            'starts_at' => '07:15',
            'ends_at' => '08:25',
            'is_active' => true,
        ]);

        Period::create([
            'number' => 2,
            'name' => 'Jam ke-2',
            'starts_at' => '08:25',
            'ends_at' => '09:25',
            'is_active' => true,
        ]);

        Period::create([
            'number' => 3,
            'name' => 'Jam ke-3',
            'starts_at' => '09:55',
            'ends_at' => '10:55',
            'is_active' => true,
        ]);

        Period::create([
            'number' => 4,
            'name' => 'Jam ke-4',
            'starts_at' => '10:55',
            'ends_at' => '12:05',
            'is_active' => true,
        ]);
    }
}
