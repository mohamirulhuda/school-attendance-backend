<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LearningGroupSeeder extends Seeder
{
    public function run(): void
    {
        $academicPeriod = AcademicPeriod::where('is_active', true)->firstOrFail();

        $learningGroups = [
            'XII A',
            'XII B',
            'XII C',
            'XII D',
            'XII E',

            'XII IPA',
            'XII IPS',
            'XII AGAMA',

            'XI A',
            'XI B',
            'XI C',
            'XI D',

            'XI IPA',
            'XI IPS',

            'X A',
            'X B',
            'X C',
            'X D',
        ];

        foreach ($learningGroups as $name) {
            LearningGroup::create([
                'academic_period_id' => $academicPeriod->id,
                'name' => $name,
                'code' => Str::upper(Str::slug($name)),
                'description' => null,
                'is_active' => true,
            ]);
        }
    }
}
