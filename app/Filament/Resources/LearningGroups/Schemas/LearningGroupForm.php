<?php

namespace App\Filament\Resources\LearningGroups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LearningGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_period_id')
                    ->relationship('academicPeriod', 'academic_year')
                    ->required(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(100),

                TextInput::make('code')
                    ->maxLength(50),

                Textarea::make('description'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
