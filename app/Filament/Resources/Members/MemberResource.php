<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Tables\MembersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'Member';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    /**
     * List only: Members are made by accepting an Invite and managed in LLDAP, never edited here.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
        ];
    }
}
