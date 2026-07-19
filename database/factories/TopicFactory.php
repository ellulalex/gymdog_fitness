<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'angle' => fake()->sentence(),
            'status' => 'queued',
            'position' => 0,
        ];
    }
}
