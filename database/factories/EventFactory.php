<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'starts_at' => now()->addWeeks(2)->setTimezone('UTC'),
            'timezone' => 'America/Sao_Paulo',
            'location' => fake()->address(),
            'theme' => fake()->optional()->words(2, true),
        ];
    }

    public function withCover(): static
    {
        return $this->state(fn (): array => [
            'cover_image_disk' => 's3',
            'cover_image_key' => 'event-covers/test-cover.jpg',
            'cover_image_mime' => 'image/jpeg',
            'cover_image_size' => 123456,
            'cover_image_width' => 1200,
            'cover_image_height' => 800,
        ]);
    }

    public function withoutCover(): static
    {
        return $this->state(fn (): array => [
            'cover_image_disk' => null,
            'cover_image_key' => null,
            'cover_image_mime' => null,
            'cover_image_size' => null,
            'cover_image_width' => null,
            'cover_image_height' => null,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDay()->setTimezone('UTC'),
        ]);
    }
}
