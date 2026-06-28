<?php

namespace Database\Factories;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Guest> */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'status' => GuestStatus::Pending,
            'adult_companions' => 0,
            'child_companions' => 0,
            'responded_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => GuestStatus::Pending,
            'adult_companions' => 0,
            'child_companions' => 0,
            'responded_at' => null,
        ]);
    }

    public function confirmed(int $adults = 0, int $children = 0): static
    {
        return $this->state(fn (): array => [
            'status' => GuestStatus::Confirmed,
            'adult_companions' => $adults,
            'child_companions' => $children,
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => GuestStatus::Declined,
            'adult_companions' => 0,
            'child_companions' => 0,
            'responded_at' => now(),
        ]);
    }

    public function withCompanions(int $adults = 1, int $children = 0): static
    {
        return $this->confirmed($adults, $children);
    }
}
