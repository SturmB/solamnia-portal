<?php

namespace App\Models;

use App\Enums\InviteStatus;
use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $accepted_at
 */
#[Fillable(['email', 'suggested_name'])]
class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use HasFactory;

    public ?string $plainTextToken = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * The query-side twin of status() === Pending: live links only. "Pending"
     * depends on the clock, so it cannot be a database constraint.
     *
     * @param  Builder<Invite>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Resolve the raw token from a URL to its Invite, whatever its state.
     */
    public static function findByPlainTextToken(string $plainTextToken): ?self
    {
        return self::where('token', hash('sha256', $plainTextToken))->first();
    }

    public function status(): InviteStatus
    {
        return match (true) {
            $this->revoked_at !== null => InviteStatus::Revoked,
            $this->accepted_at !== null => InviteStatus::Accepted,
            $this->expires_at->isPast() => InviteStatus::Expired,
            default => InviteStatus::Pending,
        };
    }

    public static function issue(string $email, string $suggestedName, User $inviter): Invite
    {
        $invite = new self([
            'email' => $email,
            'suggested_name' => $suggestedName,
        ]);
        $invite->plainTextToken = Str::random(64);

        $invite->token = hash('sha256', $invite->plainTextToken);
        $invite->expires_at = Carbon::now()->addDays(14);
        $invite->inviter()->associate($inviter);
        $invite->save();

        return $invite;
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
