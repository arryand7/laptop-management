<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LookupController extends Controller
{
    public function students(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $students = User::students()
            ->when($query !== '', function ($builder) use ($query) {
                $lower = Str::lower($query);
                $builder->where(function ($inner) use ($query, $lower) {
                    $inner->where('qr_code', $query)
                        ->orWhere('card_code', $query)
                        ->orWhere('student_number', 'like', "%{$query}%")
                        ->orWhereRaw('LOWER(name) = ?', [$lower])
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'student_number', 'card_code', 'qr_code', 'classroom']);

        return response()->json(
            $students->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'student_number' => $student->student_number,
                'card_code' => $student->card_code,
                'qr_code' => $student->qr_code,
                'classroom' => $student->classroom,
            ])
        );
    }

    public function laptops(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $laptops = Laptop::with('owner')
            ->when($query !== '', function ($builder) use ($query) {
                $lower = Str::lower($query);
                $builder->where(function ($inner) use ($query, $lower) {
                    $inner->where('qr_code', $query)
                        ->orWhere('code', $query)
                        ->orWhere('serial_number', $query)
                        ->orWhereRaw('LOWER(name) = ?', [$lower]);
                })
                ->orWhereHas('owner', function ($ownerQuery) use ($query) {
                    $ownerQuery->where('student_number', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->when($request->boolean('only_borrowed'), fn ($builder) => $builder->where('status', 'borrowed'))
            ->orderBy('code')
            ->limit(10)
            ->get(['id', 'code', 'name', 'qr_code', 'status', 'owner_id']);

        return response()->json(
            $laptops->map(fn (Laptop $laptop) => [
                'id' => $laptop->id,
                'code' => $laptop->code,
                'name' => $laptop->name,
                'qr_code' => $laptop->qr_code,
                'status' => $laptop->status,
                'owner_student_number' => $laptop->owner?->student_number,
                'owner_name' => $laptop->owner?->name,
            ])
        );
    }
}
