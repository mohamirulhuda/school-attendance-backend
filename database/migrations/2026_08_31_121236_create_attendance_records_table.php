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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->string('status', 1);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(
                ['attendance_session_id', 'student_id'],
                'attendance_session_student_unique'
            );

            $table->index(
                ['student_id', 'status'],
                'attendance_student_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
