<?php

namespace App\Filament\Resources\LearningGroupStudents;

use App\Filament\Resources\LearningGroupStudents\Pages\CreateLearningGroupStudent;
use App\Filament\Resources\LearningGroupStudents\Pages\EditLearningGroupStudent;
use App\Filament\Resources\LearningGroupStudents\Pages\ListLearningGroupStudents;
use App\Filament\Resources\LearningGroupStudents\Schemas\LearningGroupStudentForm;
use App\Filament\Resources\LearningGroupStudents\Tables\LearningGroupStudentsTable;
use App\Models\LearningGroupStudent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LearningGroupStudentResource extends Resource
{
    protected static ?string $model = LearningGroupStudent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return LearningGroupStudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningGroupStudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningGroupStudents::route('/'),
            'create' => CreateLearningGroupStudent::route('/create'),
            'edit' => EditLearningGroupStudent::route('/{record}/edit'),
        ];
    }
}
