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
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->string('academic_year', 9);
            $table->string('semester', 10);

            $table->date('starts_at');
            $table->date('ends_at');

            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->unique(['academic_year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
