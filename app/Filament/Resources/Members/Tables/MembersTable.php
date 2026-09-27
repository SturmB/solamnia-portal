<?php

namespace App\Filament\Resources\Members\Tables;

use App\Jobs\ShareMediaLibraries;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('retryMediaServer')
                    ->label('Retry media-server invite')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (User $record): void {
                        ShareMediaLibraries::dispatch($record->email);
                        Notification::make()
                            ->title('Media-server invite queued')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
