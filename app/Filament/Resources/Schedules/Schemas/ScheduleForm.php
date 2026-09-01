<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\DayOfWeek;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_period_id')
                    ->relationship('academicPeriod', 'academic_year')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('learning_group_id')
                    ->relationship('learningGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('period_id')
                    ->relationship('period', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('day_of_week')
                    ->options(DayOfWeek::class)
                    ->required(),
            ]);
    }
}
