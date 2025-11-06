<?php

namespace Database\Factories;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BorrowTransaction>
 */
class BorrowTransactionFactory extends Factory
{
    protected $model = BorrowTransaction::class;

    public function definition(): array
    {
        $borrowedAt = now()->subHours(fake()->numberBetween(1, 6));
        $dueAt = $borrowedAt->copy()->addHours(fake()->numberBetween(4, 10));

        return [
            'transaction_code' => 'TRX-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)),
            'student_id' => User::factory()->student(),
            'laptop_id' => Laptop::factory(),
            'staff_id' => User::factory()->staff(),
            'return_staff_id' => null,
            'usage_purpose' => 'Peminjaman ' . fake()->word(),
            'status' => 'borrowed',
            'was_late' => false,
            'borrowed_at' => $borrowedAt,
            'due_at' => $dueAt,
            'returned_at' => null,
            'late_minutes' => null,
            'staff_notes' => null,
        ];
    }

    public function returned(bool $late = false): static
    {
        return $this->state(function () use ($late) {
            $borrowedAt = now()->subHours(8);
            $dueAt = $borrowedAt->copy()->addHours(4);
            $returnedAt = $late ? $dueAt->copy()->addHour() : $dueAt->copy()->subHour();

            return [
                'status' => 'returned',
                'was_late' => $late,
                'returned_at' => $returnedAt,
                'due_at' => $dueAt,
                'borrowed_at' => $borrowedAt,
                'late_minutes' => $late ? $dueAt->diffInMinutes($returnedAt) : null,
            ];
        });
    }
}
