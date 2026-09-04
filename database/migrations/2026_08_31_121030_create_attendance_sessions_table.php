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
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->restrictOnDelete();

            $table->foreignId('teacher_id_snapshot')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->foreignId('subject_id_snapshot')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->foreignId('learning_group_id_snapshot')
                ->constrained('learning_groups')
                ->restrictOnDelete();

            $table->foreignId('period_id_snapshot')
                ->constrained('periods')
                ->restrictOnDelete();

            $table->date('date');

            $table->string('status', 20)->default('draft');

            $table->timestamp('opened_at')->nullable();

            $table->foreignId('finalized_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('finalized_at')->nullable();

            $table->unique(
                ['schedule_id', 'date'],
                'attendance_schedule_date_unique'
            );

            $table->index(
                ['date', 'status'],
                'attendance_date_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
