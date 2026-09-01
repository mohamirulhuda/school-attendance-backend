<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $academicPeriod = AcademicPeriod::where('is_active', true)->firstOrFail();

        $path = database_path('seeders/data/schedule.json');

        if (! file_exists($path)) {
            throw new RuntimeException(
                'File schedule_v2.json tidak ditemukan.'
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
                $learningGroup = LearningGroup::where(
                    'academic_period_id',
                    $academicPeriod->id
                )
                    ->where('name', $item['learning_group'])
                    ->firstOrFail();

                $subject = Subject::where(
                    'code',
                    $item['subject']
                )->firstOrFail();

                $teacher = Teacher::findOrFail($item['teacher_id']);

                $period = Period::where(
                    'number',
                    $item['period']
                )->firstOrFail();

                Schedule::create([
                    'academic_period_id' => $academicPeriod->id,
                    'learning_group_id' => $learningGroup->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'period_id' => $period->id,
                    'day_of_week' => $item['day_of_week'],
                ]);
            }
        });
    }
}
