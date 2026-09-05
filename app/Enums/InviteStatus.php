<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum InviteStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Accepted = 'accepted';

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Expired => 'gray',
            self::Revoked => 'danger',
            self::Accepted => 'success',
        };
    }

    public function getIcon(): BackedEnum|Htmlable
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Expired => Heroicon::OutlinedXCircle,
            self::Revoked => Heroicon::OutlinedNoSymbol,
            self::Accepted => Heroicon::OutlinedCheckCircle,
        };
    }

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
