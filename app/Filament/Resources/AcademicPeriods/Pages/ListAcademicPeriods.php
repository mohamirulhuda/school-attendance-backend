<?php

namespace App\Filament\Resources\AcademicPeriods\Pages;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicPeriods extends ListRecords
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
