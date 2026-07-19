<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'name' => null,
            'price_cents' => fake()->numberBetween(999, 9999),
            'compare_at_price_cents' => null,
            'stock_qty' => fake()->numberBetween(0, 50),
            'position' => 0,
        ];
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attrs) => [
            'compare_at_price_cents' => (int) round($attrs['price_cents'] * 1.25),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_qty' => 0]);
    }
}
