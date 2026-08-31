<?php

namespace App\Filament\Resources\LearningGroups;

use App\Filament\Resources\LearningGroups\Pages\CreateLearningGroup;
use App\Filament\Resources\LearningGroups\Pages\EditLearningGroup;
use App\Filament\Resources\LearningGroups\Pages\ListLearningGroups;
use App\Filament\Resources\LearningGroups\Schemas\LearningGroupForm;
use App\Filament\Resources\LearningGroups\Tables\LearningGroupsTable;
use App\Models\LearningGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LearningGroupResource extends Resource
{
    protected static ?string $model = LearningGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?string $navigationLabel = 'Learning Groups';

    protected static ?string $modelLabel = 'Learning Group';

    protected static ?string $pluralModelLabel = 'Learning Groups';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LearningGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningGroupsTable::configure($table);
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
            'index' => ListLearningGroups::route('/'),
            'create' => CreateLearningGroup::route('/create'),
            'edit' => EditLearningGroup::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
