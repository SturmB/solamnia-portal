<?php

namespace App\Filament\Resources\Invites\Tables;

use App\Enums\InviteStatus;
use App\Mail\InviteMail;
use App\Models\Invite;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class InvitesTable
{
    public static function configure(Table $table): Table
    {
        $isPending = fn (Invite $record): bool => $record->status() === InviteStatus::Pending;
        $canResend = fn (Invite $record): bool => $isPending($record) && $record->plain_token !== null;

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
            ])
            ->recordActions([
                Action::make('copyLink')
                    ->visible($canResend)
                    ->label('Copy link')
                    ->icon(Heroicon::OutlinedClipboard)
                    ->schema([
                        TextEntry::make('url')
                            ->label('Invite link')
                            ->state(fn (Invite $record): string => route('invites.show', $record->plain_token))
                            ->copyable()
                            ->copyMessage('Link copied'),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('resend')
                    ->visible($canResend)
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->action(function (Invite $record): void {
                        Mail::to($record->email)->send(new InviteMail($record, $record->plain_token));
                        Notification::make()
                            ->title('Invite re-sent')
                            ->success()
                            ->send();
                    }),
                Action::make('revoke')
                    ->visible($isPending)
                    ->color('danger')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->requiresConfirmation()
                    ->modalHeading('Revoke this Invite?')
                    ->modalDescription('When revoked, the link stops working immediately.')
                    ->action(function (Invite $record): void {
                        if ($record->revoke()) {
                            Notification::make()
                                ->title('Invite revoked')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Invite was no longer pending')
                                ->warning()
                                ->send();
                        }
                    }),
            ]);
    }
}
