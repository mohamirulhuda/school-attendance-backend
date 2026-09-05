<?php

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use App\Models\LearningGroupStudent;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Spatie\Permission\Models\Role;

function createAttendanceTestData(): array
{
    Role::firstOrCreate([
        'name' => 'guru_mapel',
        'guard_name' => 'web',
    ]);

    $teacher = Teacher::create([
        'name' => 'Test Teacher',
        'email' => 'test.teacher@example.com',
        'gender' => 'L',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'email' => $teacher->email,
    ]);

    $user->assignRole('guru_mapel');

    $subject = Subject::create([
        'code' => 'TEST',
        'name' => 'Test Subject',
        'is_active' => true,
    ]);

    $academicPeriod = AcademicPeriod::create([
        'academic_year' => '2026/2027',
        'semester' => \App\Enums\Semester::Odd,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-12-31',
        'is_active' => true,
    ]);

    $learningGroup = LearningGroup::create([
        'academic_period_id' => $academicPeriod->id,
        'name' => 'Test Class',
        'code' => 'TEST',
        'is_active' => true,
    ]);

    $period = Period::create([
        'number' => 99,
        'name' => 'Test Period',
        'starts_at' => '07:00',
        'ends_at' => '08:00',
        'is_active' => true,
    ]);

    $students = collect([
        Student::create([
            'name' => 'Test Student 1',
            'gender' => 'L',
            'is_active' => true,
        ]),
        Student::create([
            'name' => 'Test Student 2',
            'gender' => 'P',
            'is_active' => true,
        ]),
    ]);

    foreach ($students as $student) {
        LearningGroupStudent::create([
            'learning_group_id' => $learningGroup->id,
            'student_id' => $student->id,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
        ]);
    }

    $schedule = Schedule::create([
        'academic_period_id' => $academicPeriod->id,
        'learning_group_id' => $learningGroup->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'period_id' => $period->id,
        'day_of_week' => 1,
    ]);

    return compact(
        'user',
        'teacher',
        'subject',
        'academicPeriod',
        'learningGroup',
        'period',
        'students',
        'schedule',
    );
}
