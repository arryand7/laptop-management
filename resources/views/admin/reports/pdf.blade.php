<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman Laptop</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { font-size: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background-color: #f8fafc; text-transform: uppercase; font-size: 10px; letter-spacing: .05em; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-size: 9px; font-weight: bold; }
        .badge-blue { background-color: #bfdbfe; color: #1d4ed8; }
        .badge-green { background-color: #bbf7d0; color: #047857; }
        .badge-rose { background-color: #fecdd3; color: #be123c; }
    </style>
</head>
<body>
    <h1>Laporan Peminjaman Laptop</h1>
    <p class="meta">Rentang: {{ $filters['start_date']->translatedFormat('d M Y') }} &rarr; {{ $filters['end_date']->translatedFormat('d M Y') }} · Status: {{ ucfirst($filters['status']) }} · Total data: {{ $transactions->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
                <th>Terlambat (m)</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Laptop</th>
                <th>Keperluan</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_code }}</td>
                    <td>{{ $transaction->borrowed_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->returned_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>
                        @if($transaction->status === 'borrowed')
                            <span class="badge badge-blue">Dipinjam</span>
                        @elseif($transaction->was_late)
                            <span class="badge badge-rose">Terlambat</span>
                        @else
                            <span class="badge badge-green">Dikembalikan</span>
                        @endif
                    </td>
                    <td>{{ $transaction->late_minutes ?? 0 }}</td>
                    <td>{{ $transaction->student?->name }}</td>
                    <td>{{ $transaction->student?->classroom }}</td>
                    <td>{{ $transaction->laptop?->name }}</td>
                    <td>{{ $transaction->usage_purpose }}</td>
                    <td>{{ $transaction->staff?->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
