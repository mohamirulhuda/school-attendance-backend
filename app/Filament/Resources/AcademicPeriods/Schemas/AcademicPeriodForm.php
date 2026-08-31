<?php

namespace App\Filament\Resources\AcademicPeriods\Schemas;

use App\Enums\Semester;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('academic_year')
                    ->required()
                    ->maxLength(9)
                    ->placeholder('2026/2027'),

                Select::make('semester')
                    ->options(Semester::class)
                    ->required(),

                DatePicker::make('starts_at')
                    ->required(),

                DatePicker::make('ends_at')
                    ->required(),
                    
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
