<?php

namespace App\Filament\Resources\Invites\Schemas;

use App\Models\Invite;
use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InviteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        // An existing Member's email is refused outright.
                        // So is one that already holds a live link; expired,
                        // revoked and accepted Invites do not block re-issuing.
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $email = Str::lower($value);
                            if (User::where('email', $email)->exists()) {
                                $fail('This email already belongs to a Member.');
                            }
                            if (Invite::pending()->where('email', $email)->exists()) {
                                $fail('A pending Invite already exists for this email.');
                            }
                        },
                    ]),

                TextInput::make('suggested_name')
                    ->label('Suggested display name')
                    ->helperText('Pre-fills the name on the acceptance page; the invitee can change it.')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
