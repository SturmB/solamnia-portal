<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Schedule (or "send now" = schedule for now) — the single send-time path from ADR-0003.
            Action::make('schedule')
                ->label('Schedule send')
                ->icon(Heroicon::Clock)
                // Hide once sent: a sent campaign must never be re-scheduled.
                ->visible(fn (Campaign $record): bool => $record->status() !== CampaignStatus::Sent)
                ->schema([
                    DateTimePicker::make('scheduled_at')
                        ->label('Send at')
                        ->seconds(false)
                        ->default(now())
                        ->required(),
                ])
                // Pre-fill with the existing time when re-scheduling, else now.
                ->fillForm(fn (Campaign $record): array => [
                    'scheduled_at' => $record->scheduled_at ?? now(),
                ])
                ->modalHeading('Schedule this campaign')
                // The recipient-count confirmation: the Admin sees the live count and must click Confirm.
                ->modalDescription(fn (): string => 'This will send to '.Subscriber::whereNull('unsubscribed_at')->count().' opted-in subscriber(s). Leave the time as-is to send within the next minute.')
                ->modalSubmitActionLabel('Confirm & schedule')
                ->action(function (array $data, Campaign $record): void {
                    // Direct assignment (not ->update()) — scheduled_at is system-managed, not $fillable.
                    $record->scheduled_at = $data['scheduled_at'];
                    $record->save();

                    Notification::make()
                        ->title('Campaign scheduled')
                        ->success()
                        ->send();
                }),
            Action::make('testSend')
                ->label('Send test to myself')
                ->icon(Heroicon::PaperAirplane)
                ->action(function (): void {
                    /** @var Campaign $campaign */
                    $campaign = $this->getRecord();

                    $admin = auth()->user();

                    Mail::to($admin)->send(new CampaignMail($campaign));

                    Notification::make()
                        ->title("Test sent to {$admin->email}")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
