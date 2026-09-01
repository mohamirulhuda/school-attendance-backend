<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/teachers.json');

        $teachers = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($teachers as $teacher) {
            Teacher::create($teacher);
        }
    }
}
