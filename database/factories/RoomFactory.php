<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'number' => (string) fake()->unique()->numberBetween(101, 999),
            'capacity' => fake()->numberBetween(1, 5),
            'price' => fake()->numberBetween(5000, 50000), // Price in cents
            'floor_id' => Floor::inRandomOrder()->first()->id ?? Floor::factory(),
            'created_by' => function () {
                return User::role(['Admin', 'Manager'])->inRandomOrder()->first()?->id ?? User::factory()->create()->assignRole('Admin')->id;
            },
        ];
    }
}
