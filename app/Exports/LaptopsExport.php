<?php

namespace App\Exports;

use App\Models\Laptop;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaptopsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection(): Collection
    {
        return Laptop::with('owner')->orderBy('code')->get();
    }

    public function map($laptop): array
    {
        $specs = $laptop->specifications ?? [];

        return [
            $laptop->code,
            $laptop->name,
            $laptop->brand,
            $laptop->model,
            $laptop->serial_number,
            $laptop->status,
            $laptop->owner?->student_number,
            $laptop->notes,
            $specs['cpu'] ?? '',
            $specs['ram'] ?? '',
            $specs['storage'] ?? '',
            $specs['os'] ?? '',
            $laptop->qr_code,
        ];
    }

    public function headings(): array
    {
        return ['code', 'name', 'brand', 'model', 'serial_number', 'status', 'owner_student_number', 'notes', 'spec_cpu', 'spec_ram', 'spec_storage', 'spec_os', 'qr_code'];
    }
}
