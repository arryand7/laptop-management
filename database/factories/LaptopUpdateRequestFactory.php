<?php

namespace Database\Factories;

use App\Models\Laptop;
use App\Models\LaptopUpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaptopUpdateRequestFactory extends Factory
{
    protected $model = LaptopUpdateRequest::class;

    public function definition(): array
    {
        return [
            'laptop_id' => Laptop::factory(),
            'student_id' => User::factory()->student(),
            'original_data' => null,
            'proposed_data' => [
                'name' => fake()->word() . ' Laptop',
            ],
            'status' => LaptopUpdateRequest::STATUS_PENDING,
        ];
    }
}
