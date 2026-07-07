<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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
