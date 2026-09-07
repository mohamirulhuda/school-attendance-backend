<?php

namespace App\Services\Attendance\Migration;

use Illuminate\Support\Collection;

class MigrationResult
{
    /**
     * @param  Collection<int, MigrationResultRow>  $rows
     */
    public function __construct(
        public readonly array $source,
        public readonly Collection $rows,
    ) {
    }

    public function summary(): array
    {
        return [
            'total' => $this->rows->count(),
            'match' => $this->countByStatus(MigrationStatus::Match),
            'historical_shift' => $this->countByStatus(MigrationStatus::HistoricalShift),
            'skip' => $this->countByStatus(MigrationStatus::Skip),
        ];
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'summary' => $this->summary(),
            'rows' => $this->rows
                ->map(fn (MigrationResultRow $row) => $row->toArray())
                ->values()
                ->all(),
        ];
    }

    private function countByStatus(MigrationStatus $status): int
    {
        return $this->rows
            ->filter(
                fn (MigrationResultRow $row) =>
                    ($row->migration['status'] ?? null) === $status->value
            )
            ->count();
    }
}
