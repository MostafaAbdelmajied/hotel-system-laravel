<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FloorFactory extends Factory
{
    protected $model = Floor::class;

    public function definition(): array
    {
        return [
            'name' => 'F'.fake()->unique()->regexify('[A-Za-z]{3}'),
            'created_by' => function () {
                return User::role(['Admin', 'Manager'])->inRandomOrder()->first()?->id ?? User::factory()->create()->assignRole('Admin')->id;
            },

        ];
    }
}
