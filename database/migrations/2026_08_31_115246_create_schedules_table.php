<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('academic_period_id')
                ->constrained('academic_periods')
                ->restrictOnDelete();

            $table->foreignId('learning_group_id')
                ->constrained('learning_groups')
                ->restrictOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->foreignId('period_id')
                ->constrained('periods')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('day_of_week');

            $table->timestamps();

            $table->unique(
                [
                    'academic_period_id',
                    'learning_group_id',
                    'period_id',
                    'day_of_week',
                ],
                'schedule_group_period_day_unique'
            );


            $table->unique(
                [
                    'academic_period_id',
                    'teacher_id',
                    'period_id',
                    'day_of_week',
                ],
                'schedule_teacher_period_day_unique'
            );

            $table->index([
                'academic_period_id',
                'day_of_week',
                'period_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
