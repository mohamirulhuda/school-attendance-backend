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
        Schema::create('learning_groups', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('academic_period_id')
                ->constrained('academic_periods')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['academic_period_id', 'is_active']);

            $table->unique(
                ['academic_period_id', 'code'],
                'learning_groups_period_code_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_groups');
    }
};
