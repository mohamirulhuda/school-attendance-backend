<?php

namespace App\Filament\Resources\LearningGroupStudents\Pages;

use App\Filament\Resources\LearningGroupStudents\LearningGroupStudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningGroupStudents extends ListRecords
{
    protected static string $resource = LearningGroupStudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
