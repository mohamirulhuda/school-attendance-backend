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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('academic_period_id')
                ->constrained('academic_periods')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->restrictOnDelete();

            $table->date('starts_at');
            $table->date('ends_at')->nullable();

            $table->timestamps();

            $table->index(['academic_period_id', 'classroom_id']);
            $table->index(['student_id', 'academic_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
