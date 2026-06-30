<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\GuestCompanion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GuestCompanion> */
class GuestCompanionFactory extends Factory
{
    protected $model = GuestCompanion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'name' => fake()->name(),
            'is_child' => false,
        ];
    }

    public function child(): static
    {
        return $this->state(fn (): array => [
            'is_child' => true,
        ]);
    }
}
