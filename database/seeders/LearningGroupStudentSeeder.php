<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LearningGroupStudentSeeder extends Seeder
{
    public function run(): void
    {
        $academicPeriod = AcademicPeriod::where('is_active', true)->firstOrFail();

        $path = database_path('seeders/data/learning_group_students.json');

        if (! file_exists($path)) {
            throw new RuntimeException(
                'File learning_group_students.json tidak ditemukan.'
            );
        }

        $data = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($data, $academicPeriod) {
            foreach ($data as $item) {
                $student = Student::findOrFail($item['student_id']);

                $learningGroup = LearningGroup::where(
                    'academic_period_id',
                    $academicPeriod->id
                )
                    ->where('name', $item['learning_group'])
                    ->firstOrFail();

                LearningGroupStudent::create([
                    'learning_group_id' => $learningGroup->id,
                    'student_id' => $student->id,
                    'starts_at' => $academicPeriod->starts_at,
                    'ends_at' => $academicPeriod->ends_at,
                ]);
            }
        });
    }
}
