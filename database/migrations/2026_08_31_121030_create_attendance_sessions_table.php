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

            $table->date('date');

            $table->string('status', 20)->default('open');

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

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
