<?php

namespace App\Services\AcademicPeriod;

use App\Models\AcademicPeriod;
use Illuminate\Support\Facades\DB;

class ManageAcademicPeriod
{
    public function create(array $data): AcademicPeriod
    {
        return DB::transaction(function () use ($data) {
            return AcademicPeriod::query()->create($data);
        });
    }

    public function update(
        AcademicPeriod $academicPeriod,
        array $data,
    ): AcademicPeriod
    {
        return DB::transaction(function () use ($academicPeriod, $data) {
            $academicPeriod->update($data);

            return $academicPeriod->refresh();
        });
    }
}
