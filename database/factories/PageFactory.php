<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'content' => '{"content": "'.$this->faker->paragraph.'"}',
            'slug' => $this->faker->unique()->slug,
            'published_at' => now(),
            'author_id' => User::factory(),
        ];
    }
}
