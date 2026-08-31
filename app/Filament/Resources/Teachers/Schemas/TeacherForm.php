<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Enums\Gender;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nip')
                    ->maxLength(30),

                TextInput::make('title_prefix')
                    ->maxLength(50),

                TextInput::make('name')
                    ->required(),

                TextInput::make('nickname')
                    ->maxLength(100),

                TextInput::make('title_suffix')
                    ->maxLength(50),

                Select::make('gender')
                    ->options(Gender::class)
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->maxLength(30),

                Textarea::make('address'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
