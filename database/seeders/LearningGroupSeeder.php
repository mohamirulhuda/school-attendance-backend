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

        $groups = [
            'XII IPA 1',
            'XII IPA 2',
            'XII IPS 1',
            'XII IPS 2',
            'XII AGAMA 1',
            'XII AGAMA 2',
            'XI IPA 1',
            'XI IPA 2',
            'XI IPS 1',
            'XI IPS 2',
            'X TURATS',
            'X TAHFIDH',
            'X SAINS',
            'X KTI',
        ];

        foreach ($groups as $name) {
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
