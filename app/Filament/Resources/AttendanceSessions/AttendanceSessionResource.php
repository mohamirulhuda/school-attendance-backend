<?php

namespace App\Filament\Resources\AttendanceSessions;

use App\Filament\Resources\AttendanceSessions\Pages\CreateAttendanceSession;
use App\Filament\Resources\AttendanceSessions\Pages\EditAttendanceSession;
use App\Filament\Resources\AttendanceSessions\Pages\ListAttendanceSessions;
use App\Filament\Resources\AttendanceSessions\Schemas\AttendanceSessionForm;
use App\Filament\Resources\AttendanceSessions\Tables\AttendanceSessionsTable;
use App\Models\AttendanceSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceSessionResource extends Resource
{
    protected static ?string $model = AttendanceSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return AttendanceSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceSessionsTable::configure($table);
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
            'index' => ListAttendanceSessions::route('/'),
            'create' => CreateAttendanceSession::route('/create'),
            'edit' => EditAttendanceSession::route('/{record}/edit'),
        ];
    }
}
