<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'body_markdown' => '# '.fake()->sentence()."\n\n".fake()->paragraph(),
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'scheduled_at' => now()->subDay(),
            'sent_at' => now()->subHour(),
        ]);
    }
}
