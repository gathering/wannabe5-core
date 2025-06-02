<?php

namespace Database\Factories;

use App\Models\UserCrewHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCrewHistory>
 */
class UserCrewHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = UserCrewHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_name' => fake()->company().' '.fake()->numberBetween(2000, date('Y')),
            'crew_name' => fake()->slug(2),
            'title' => fake()->jobTitle(),
        ];
    }
}
