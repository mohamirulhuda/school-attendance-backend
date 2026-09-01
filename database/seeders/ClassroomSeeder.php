<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = [
            ['name' => 'A', 'grade' => 12, 'major' => 'TURATS'],
            ['name' => 'B', 'grade' => 12, 'major' => 'TAHFIDH'],
            ['name' => 'C', 'grade' => 12, 'major' => 'IPA'],
            ['name' => 'D', 'grade' => 12, 'major' => 'IPS'],
            ['name' => 'E', 'grade' => 12, 'major' => 'AGAMA'],
            ['name' => 'F', 'grade' => 12, 'major' => null],
            ['name' => 'G', 'grade' => 12, 'major' => null],

            ['name' => 'A', 'grade' => 11, 'major' => 'TURATS'],
            ['name' => 'B', 'grade' => 11, 'major' => 'TAHFIDH'],
            ['name' => 'C', 'grade' => 11, 'major' => 'IPA'],
            ['name' => 'D', 'grade' => 11, 'major' => 'IPS'],
            ['name' => 'E', 'grade' => 11, 'major' => null],
            ['name' => 'F', 'grade' => 11, 'major' => null],
            ['name' => 'G', 'grade' => 11, 'major' => null],

            ['name' => 'A', 'grade' => 10, 'major' => 'TURATS'],
            ['name' => 'B', 'grade' => 10, 'major' => 'TAHFIDH'],
            ['name' => 'C', 'grade' => 10, 'major' => 'SAINS'],
            ['name' => 'D', 'grade' => 10, 'major' => 'KTI'],
            ['name' => 'E', 'grade' => 10, 'major' => null],
            ['name' => 'F', 'grade' => 10, 'major' => null],
            ['name' => 'G', 'grade' => 10, 'major' => null],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::create([
                ...$classroom,
                'is_active' => true,
            ]);
        }
    }
}
