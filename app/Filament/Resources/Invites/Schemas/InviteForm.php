<?php

namespace App\Filament\Resources\Invites\Schemas;

use App\Models\Invite;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

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
                        Rule::unique('users', 'email'),
                        // So is one that already holds a live link; expired,
                        // revoked and accepted Invites do not block re-issuing.
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (Invite::pending()->where('email', $value)->exists()) {
                                $fail('A pending Invite already exists for this email.');
                            }
                        },
                    ])
                    ->validationMessages([
                        'unique' => 'This email already belongs to a Member.',
                    ]),

                TextInput::make('suggested_name')
                    ->label('Suggested display name')
                    ->helperText('Pre-fills the name on the acceptance page; the invitee can change it.')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
