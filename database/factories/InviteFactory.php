<?php

namespace Database\Factories;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invite>
 */
class InviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'suggested_name' => fake()->firstName(),
            'invited_by' => User::factory(),
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(14),
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'revoked_at' => now()->subHour(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'accepted_at' => now()->subHour(),
        ]);
    }
}
