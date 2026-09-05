<?php

namespace App\Filament\Resources\Invites\Tables;

use App\Enums\InviteStatus;
use App\Models\Invite;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('suggested_name')
                    ->label('Suggested name'),
                // Derived from the timestamps (Invite::status()); the enum supplies badge color, icon and label.
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Invite $record): InviteStatus => $record->status()),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('accepted_at')
                    ->label('Accepted')
                    ->dateTime()
                    ->placeholder('—'),
            ]);
    }
}
