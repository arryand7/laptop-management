<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithMapping, WithHeadings
{
    public function __construct(protected string $role = 'student')
    {
    }

    public function collection(): Collection
    {
        return User::query()
            ->where('role', $this->role)
            ->orderBy('name')
            ->get();
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->role,
            $user->student_number,
            $user->card_code,
            $user->classroom,
            $user->phone,
            $user->is_active ? 'true' : 'false',
            '',
            $user->qr_code,
        ];
    }

    public function headings(): array
    {
        return ['name', 'email', 'role', 'student_number', 'card_code', 'classroom', 'phone', 'is_active', 'password', 'qr_code'];
    }
}
