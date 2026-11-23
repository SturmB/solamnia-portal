<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fn (array $attributes): string => Str::slug($attributes['title']),
            'body_markdown' => fake()->paragraph(),
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
