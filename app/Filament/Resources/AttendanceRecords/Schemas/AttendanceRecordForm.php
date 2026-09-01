<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Enums\AttendanceStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('attendance_session_id')
                    ->relationship('attendanceSession', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record): string => sprintf(
                            '%s — %s — %s',
                            $record->schedule->learningGroup->name,
                            $record->schedule->subject->name,
                            $record->date->format('d/m/Y'),
                        )
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('student_id')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->options(AttendanceStatus::class)
                    ->required(),

                Textarea::make('note')
                    ->rows(3),
            ]);
    }
}
