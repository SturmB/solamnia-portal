<?php

namespace App\Filament\Resources\Invites\Pages;

use App\Filament\Resources\Invites\InviteResource;
use App\Mail\InviteMail;
use App\Models\Invite;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CreateInvite extends CreateRecord
{
    protected static string $resource = InviteResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Route the form through Invite::issue() so the token, expiry and inviter
     * are minted in one place, then send the link while the raw token is still
     * in memory — it exists nowhere else.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        $invite = Invite::issue($data['email'], $data['suggested_name'], $admin);

        Mail::to($invite->email)->send(new InviteMail($invite, $invite->plainTextToken));

        return $invite;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
