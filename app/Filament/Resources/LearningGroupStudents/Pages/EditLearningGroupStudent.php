<?php

namespace App\Filament\Resources\LearningGroupStudents\Pages;

use App\Filament\Resources\LearningGroupStudents\LearningGroupStudentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningGroupStudent extends EditRecord
{
    protected static string $resource = LearningGroupStudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
