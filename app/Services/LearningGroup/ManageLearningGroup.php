<?php

namespace App\Services\LearningGroup;

use App\Models\AcademicPeriod;
use App\Models\LearningGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageLearningGroup
{
    public function create(array $data): LearningGroup
    {
        return DB::transaction(function () use ($data) {
            $academicPeriod = AcademicPeriod::query()
                ->where('public_id', $data['academic_period_id'])
                ->firstOrFail();

            $data['academic_period_id'] = $academicPeriod->id;

            $this->ensureCodeIsUnique(
                $academicPeriod->id,
                $data['code'],
            );

            return LearningGroup::create($data);
        });
    }

    public function update(
        LearningGroup $learningGroup,
        array $data,
    ): LearningGroup {
        return DB::transaction(function () use ($learningGroup, $data) {
            if (isset($data['code'])) {
                $this->ensureCodeIsUnique(
                    $learningGroup->academic_period_id,
                    $data['code'],
                    $learningGroup->id,
                );
            }

            $learningGroup->update($data);

            return $learningGroup->refresh();
        });
    }

    public function delete(LearningGroup $learningGroup): void
    {
        DB::transaction(
            fn () => $learningGroup->delete(),
        );
    }

    private function ensureCodeIsUnique(
        int $academicPeriodId,
        string $code,
        ?int $exceptId = null,
    ): void {
        $query = LearningGroup::withTrashed()
            ->where('academic_period_id', $academicPeriodId)
            ->where('code', $code);

        if ($exceptId !== null) {
            $query->where('id', '<>', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => 'The code has already been used in this academic period.',
            ]);
        }
    }
}
