<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class ManageTeacher
{
    public function create(array $data): Teacher
    {
        return DB::transaction(
            fn () => Teacher::create($data)
        );
    }

    public function update(Teacher $teacher, array $data): Teacher
    {
        return DB::transaction(function () use ($teacher, $data) {
            $teacher->update($data);

            return $teacher->refresh();
        });
    }
}
