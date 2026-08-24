<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable(),
                // Derived from the timestamps (Campaign::status()); the enum supplies its own badge color/label.
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Campaign $record): CampaignStatus => $record->status()),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('recipient_count')
                    ->label('Recipients')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Per-record delete() policy check — sent Campaigns are skipped,
                    // and the policy's DenyResponse feeds the "skipped" notification.
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
