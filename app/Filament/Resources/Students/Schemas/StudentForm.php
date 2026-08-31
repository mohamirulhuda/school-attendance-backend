<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\Gender;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nis')
                    ->maxLength(30),

                TextInput::make('nisn')
                    ->maxLength(20),

                TextInput::make('name')
                    ->required(),

                Select::make('gender')
                    ->options(Gender::class)
                    ->required(),

                TextInput::make('birth_place')
                    ->maxLength(100),

                DatePicker::make('birth_date'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
