<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100),

                Select::make('grade')
                    ->options([
                        10 => '10',
                        11 => '11',
                        12 => '12',
                    ])
                    ->required(),

                TextInput::make('major')
                    ->required()
                    ->maxLength(50),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
