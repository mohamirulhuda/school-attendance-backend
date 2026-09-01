<?php

namespace App\Filament\Resources\AttendanceRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attendanceSession.schedule.learningGroup.name')
                    ->label('Learning Group')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attendanceSession.schedule.subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attendanceSession.date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
