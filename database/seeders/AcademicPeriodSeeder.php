<?php

namespace Database\Seeders;

use App\Enums\Semester;
use App\Models\AcademicPeriod;
use Illuminate\Database\Seeder;

class AcademicPeriodSeeder extends Seeder
{
    public function run(): void
    {
        AcademicPeriod::create([
            'academic_year' => '2026/2027',
            'semester' => Semester::Odd,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ]);

        AcademicPeriod::create([
            'academic_year' => '2026/2027',
            'semester' => Semester::Even,
            'starts_at' => '2027-01-01',
            'ends_at' => '2027-06-30',
            'is_active' => false,
        ]);
    }
}
