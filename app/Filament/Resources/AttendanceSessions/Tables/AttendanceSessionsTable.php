<?php

namespace App\Filament\Resources\AttendanceSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schedule.learningGroup.name')
                    ->label('Learning Group')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.period.name')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.day_of_week')
                    ->label('Day')
                    ->badge(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
