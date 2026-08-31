<?php

namespace App\Filament\Resources\Periods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->numeric()
                    ->required(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(50),

                TimePicker::make('starts_at')
                    ->required(),

                TimePicker::make('ends_at')
                    ->required(),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
