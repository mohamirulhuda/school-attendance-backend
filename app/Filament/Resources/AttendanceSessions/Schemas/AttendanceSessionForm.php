<?php

namespace App\Filament\Resources\AttendanceSessions\Schemas;

use App\Enums\AttendanceSessionStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AttendanceSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('schedule_id')
                    ->relationship('schedule', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record): string => sprintf(
                            '%s — %s — %s — %s — %s',
                            $record->learningGroup->name,
                            $record->subject->name,
                            $record->teacher->name,
                            $record->period->name,
                            $record->day_of_week->name,
                        )
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('date')
                    ->required(),

                Select::make('status')
                    ->options(AttendanceSessionStatus::class)
                    ->default(AttendanceSessionStatus::Open)
                    ->required(),
            ]);
    }
}
