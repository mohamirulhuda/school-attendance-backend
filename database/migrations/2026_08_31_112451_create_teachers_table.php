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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->string('nip', 30)->nullable()->unique();

            $table->string('title_prefix', 50)->nullable();
            $table->string('name');
            $table->string('nickname', 100)->nullable();
            $table->string('title_suffix', 50)->nullable();

            $table->string('gender', 1);

            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->text('address')->nullable();

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
        Schema::dropIfExists('teachers');
    }
};
