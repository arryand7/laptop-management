<?php

namespace App\Exports;

use App\Models\BorrowTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BorrowReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected array $filters)
    {
    }

    public function collection(): Collection
    {
        return BorrowTransaction::with(['student', 'laptop', 'staff'])
            ->whereBetween('borrowed_at', [$this->filters['start_date'], $this->filters['end_date']])
            ->when($this->filters['status'] === 'borrowed', fn ($query) => $query->where('status', 'borrowed'))
            ->when($this->filters['status'] === 'returned', fn ($query) => $query->where('status', 'returned')->where('was_late', false))
            ->when($this->filters['status'] === 'late', fn ($query) => $query->where('status', 'returned')->where('was_late', true))
            ->orderBy('borrowed_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Tanggal Pinjam',
            'Tanggal Kembali',
            'Status',
            'Terlambat (menit)',
            'Siswa',
            'Kelas',
            'Laptop',
            'Keperluan',
            'Petugas',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_code,
            optional($transaction->borrowed_at)->format('Y-m-d H:i'),
            optional($transaction->returned_at)->format('Y-m-d H:i'),
            $this->statusLabel($transaction),
            $transaction->late_minutes ?? 0,
            $transaction->student?->name,
            $transaction->student?->classroom,
            $transaction->laptop?->name,
            $transaction->usage_purpose,
            $transaction->staff?->name,
        ];
    }

    protected function statusLabel(BorrowTransaction $transaction): string
    {
        if ($transaction->status === 'borrowed') {
            return 'Dipinjam';
        }

        if ($transaction->was_late) {
            return 'Dikembalikan (Terlambat)';
        }

        return 'Dikembalikan Tepat Waktu';
    }
}
