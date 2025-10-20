<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = fake()->randomElement(['admin', 'staff', 'student']);
        $cardCode = $role === 'student' ? Str::random(64) : null;

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => $role,
            'student_number' => $role === 'student' ? fake()->unique()->numerify('STD######') : null,
            'card_code' => $cardCode,
            'classroom' => $role === 'student' ? fake()->randomElement(['X A', 'X B', 'XI A', 'XI B', 'XII A']) : null,
            'phone' => fake()->phoneNumber(),
            'qr_code' => $cardCode ?? strtoupper(Str::random(10)),
            'violations_count' => 0,
            'sanction_ends_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'student_number' => null,
            'classroom' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn () => [
            'role' => 'staff',
            'student_number' => null,
            'classroom' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'role' => 'student',
        ]);
    }
}
