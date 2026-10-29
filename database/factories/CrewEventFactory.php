<?php

namespace Database\Factories;

use App\Models\CrewEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrewEvent>
 */
class CrewEventFactory extends Factory
{
    protected $model = CrewEvent::class;

    public function definition(): array
    {
        return [
            'name' => 'The Gathering '.fake()->unique()->numberBetween(2024, 2035),
            'slug' => 'tg'.fake()->unique()->numberBetween(24, 35),
            'location' => 'Vikingskipet, Hamar',
            'start_date' => now()->addDays(60)->toDateString(),
            'end_date' => now()->addDays(74)->toDateString(),
            'visible' => true,
            'active' => true,
            'applications_open' => true,
        ];
    }
}
