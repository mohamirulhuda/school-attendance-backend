<?php

namespace App\Services\Classroom;

use App\Models\Classroom;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageClassroom
{
    public function create(array $data): Classroom
    {
        return DB::transaction(function () use ($data) {
            $this->ensureUniqueGradeAndName(
                $data['grade'],
                $data['name'],
            );

            return Classroom::create($data);
        });
    }

    public function update(Classroom $classroom, array $data): Classroom
    {
        return DB::transaction(function () use ($classroom, $data) {
            $classroom->update($data);

            return $classroom->refresh();
        });
    }

    private function ensureUniqueGradeAndName(int $grade, string $name): void
    {
        if (
            Classroom::withTrashed()
                ->where('grade', $grade)
                ->where('name', $name)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => 'The classroom grade and name combination has already been used.',
            ]);
        }
    }
}
