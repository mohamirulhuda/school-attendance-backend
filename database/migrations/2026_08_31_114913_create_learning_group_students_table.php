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
        Schema::create('learning_group_students', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('learning_group_id')
                ->constrained('learning_groups')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->date('starts_at');
            $table->date('ends_at')->nullable();

            $table->timestamps();

            $table->index(['learning_group_id', 'student_id']);
            $table->index(['student_id', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_group_students');
    }
};
