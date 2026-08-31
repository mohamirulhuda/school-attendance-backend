<?php

namespace App\Filament\Resources\AcademicPeriods;

use App\Filament\Resources\AcademicPeriods\Pages\CreateAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\EditAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\ListAcademicPeriods;
use App\Filament\Resources\AcademicPeriods\Schemas\AcademicPeriodForm;
use App\Filament\Resources\AcademicPeriods\Tables\AcademicPeriodsTable;
use App\Models\AcademicPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AcademicPeriodResource extends Resource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Academic Periods';

    protected static ?string $modelLabel = 'Academic Period';

    protected static ?string $pluralModelLabel = 'Academic Periods';

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'academic_year';

    public static function form(Schema $schema): Schema
    {
        return AcademicPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicPeriodsTable::configure($table);
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
            'index' => ListAcademicPeriods::route('/'),
            'create' => CreateAcademicPeriod::route('/create'),
            'edit' => EditAcademicPeriod::route('/{record}/edit'),
        ];
    }
}
