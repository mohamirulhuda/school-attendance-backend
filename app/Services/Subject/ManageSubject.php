<?php

namespace App\Services\Subject;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class ManageSubject
{
    public function create(array $data): Subject
    {
        return DB::transaction(fn () => Subject::create($data));
    }

    public function update(Subject $subject, array $data): Subject
    {
        return DB::transaction(function () use ($subject, $data) {
            $subject->update($data);

            return $subject->refresh();
        });
    }

    public function delete(Subject $subject): void
    {
        DB::transaction(fn () => $subject->delete());
    }
}
