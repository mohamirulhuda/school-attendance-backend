<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ManageStudent
{
    public function create(array $data): Student
    {
        return DB::transaction(
            fn () => Student::create($data)
        );
    }

    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $student->update($data);

            return $student->refresh();
        });
    }
}
