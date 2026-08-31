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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->string('nis', 30)->nullable()->unique();
            $table->string('nisn', 20)->nullable()->unique();

            $table->string('name');

            $table->string('gender', 1);

            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
