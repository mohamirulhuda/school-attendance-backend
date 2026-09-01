<?php

namespace App\Filament\Resources\LearningGroupStudents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class LearningGroupStudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('learning_group_id')
                    ->relationship('learningGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('student_id')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('starts_at')
                    ->required(),

                DatePicker::make('ends_at'),
            ]);
    }
}
