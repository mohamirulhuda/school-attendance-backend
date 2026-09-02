<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Dev Admin',
            'email' => 'dev@example.com',
        ]);

        $this->call([
            AcademicPeriodSeeder::class,
            PeriodSeeder::class,
            ClassroomSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            SubjectSeeder::class,
            LearningGroupSeeder::class,
            EnrollmentSeeder::class,
            LearningGroupStudentSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}
