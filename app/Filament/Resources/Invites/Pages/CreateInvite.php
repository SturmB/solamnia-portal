<?php

namespace App\Filament\Resources\Invites\Pages;

use App\Filament\Resources\Invites\InviteResource;
use App\Models\Invite;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvite extends CreateRecord
{
    protected static string $resource = InviteResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Route the form through Invite::issue() so the token, expiry and inviter
     * are minted in one place, then send the link.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        $invite = Invite::issue($data['email'], $data['suggested_name'], $admin);

        $invite->sendLink();

        return $invite;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
