<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Laptop>
 */
class LaptopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('LP-###??')),
            'name' => fake()->randomElement(['Laptop Lab', 'Laptop Library', 'Laptop Presentation']) . ' ' . fake()->numberBetween(1, 99),
            'brand' => fake()->randomElement(['Dell', 'Lenovo', 'HP', 'Acer', 'Asus']),
            'model' => strtoupper(fake()->bothify('MOD-###')),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN########')),
            'specifications' => [
                'cpu' => fake()->randomElement(['Intel i5', 'Intel i7', 'AMD Ryzen 5']),
                'ram' => fake()->randomElement(['8GB', '16GB']),
                'storage' => fake()->randomElement(['256GB SSD', '512GB SSD']),
                'os' => fake()->randomElement(['Windows 11', 'Windows 10']),
            ],
            'status' => 'available',
            'owner_id' => null,
            'qr_code' => strtoupper(Str::uuid()->toString()),
            'notes' => null,
            'last_checked_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }
}
