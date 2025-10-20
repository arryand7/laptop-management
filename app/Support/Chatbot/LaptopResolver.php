<?php

namespace App\Support\Chatbot;

use App\Models\Laptop;
use Illuminate\Support\Collection;

class LaptopResolver
{
    public static function resolve(string $identifier): Collection
    {
        $identifier = trim($identifier);
        $query = Laptop::query()->with('owner');

        if (preg_match('/^[A-Z]{2,}-/', $identifier)) {
            $query->where('code', $identifier);
        } elseif (preg_match('/^\d{5,}$/', $identifier)) {
            $query->whereHas('owner', function ($q) use ($identifier) {
                $q->where('student_number', $identifier);
            });
        } else {
            $query->where(function ($q) use ($identifier) {
                $q->where('code', 'like', "%{$identifier}%")
                    ->orWhere('name', 'like', "%{$identifier}%")
                    ->orWhere('serial_number', 'like', "%{$identifier}%");
            });
        }

        return $query->take(5)->get();
    }
}
