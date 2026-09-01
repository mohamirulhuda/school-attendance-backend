<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Seeder;
use RuntimeException;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/enrollments.json');

        $data = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $activePeriods = AcademicPeriod::where('is_active', true)->get();

        if ($activePeriods->count() !== 1) {
            throw new RuntimeException(
                'EnrollmentSeeder membutuhkan tepat satu Academic Period aktif.'
            );
        }

        $academicPeriod = $activePeriods->first();

        foreach ($data as $item) {
            $student = Student::find($item['student_id']);

            if (!$student) {
                throw new RuntimeException(
                    "Student dengan ID {$item['student_id']} tidak ditemukan."
                );
            }

            [$gradeName, $className] = explode(' ', $item['classroom'], 2);

            $grade = match ($gradeName) {
                'X' => 10,
                'XI' => 11,
                'XII' => 12,
                default => throw new RuntimeException(
                    "Grade {$gradeName} tidak valid."
                ),
            };

            $classroom = Classroom::where('grade', $grade)
                ->where('name', $className)
                ->first();
            if (!$classroom) {
                throw new RuntimeException(
                    "Classroom {$item['classroom']} tidak ditemukan."
                );
            }

            Enrollment::create([
                'academic_period_id' => $academicPeriod->id,
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'starts_at' => $academicPeriod->starts_at,
                'ends_at' => $academicPeriod->ends_at,
            ]);
        }
    }
}
