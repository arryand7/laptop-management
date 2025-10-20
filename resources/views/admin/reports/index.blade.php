@extends('layouts.app')

@section('title', 'Laporan Peminjaman')

@section('content')
    <h1 class="text-xl font-semibold text-slate-800">Laporan Peminjaman Laptop</h1>
    <p class="mt-1 text-sm text-slate-500">Ekspor laporan ke Excel atau PDF sesuai kebutuhan periode.</p>

    <form method="GET" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-4">
        <div>
            <label for="start_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Mulai</label>
            <input type="date" id="start_date" name="start_date" value="{{ optional($filters['start_date'])->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label for="end_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Selesai</label>
            <input type="date" id="end_date" name="end_date" value="{{ optional($filters['end_date'])->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @foreach(['all' => 'Semua', 'borrowed' => 'Sedang Dipinjam', 'returned' => 'Dikembalikan', 'late' => 'Pengembalian Terlambat'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
        </div>
    </form>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600">
        <span>Rentang: {{ $filters['start_date']->translatedFormat('d M Y') }} &rarr; {{ $filters['end_date']->translatedFormat('d M Y') }}</span>
        <span>Status: <strong>{{ ucfirst($filters['status']) }}</strong></span>
        <span>Total data: {{ $transactions->count() }}</span>
    </div>

    <div class="mt-4 flex gap-3">
        <form method="POST" action="{{ route('admin.reports.export.excel') }}">
            @csrf
            <input type="hidden" name="start_date" value="{{ $filters['start_date']->format('Y-m-d') }}">
            <input type="hidden" name="end_date" value="{{ $filters['end_date']->format('Y-m-d') }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Export Excel</button>
        </form>
        <form method="POST" action="{{ route('admin.reports.export.pdf') }}">
            @csrf
            <input type="hidden" name="start_date" value="{{ $filters['start_date']->format('Y-m-d') }}">
            <input type="hidden" name="end_date" value="{{ $filters['end_date']->format('Y-m-d') }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500">Export PDF</button>
        </form>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm datatable-default w-100">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal Pinjam</th>
                    <th class="px-4 py-3">Siswa</th>
                    <th class="px-4 py-3">Laptop</th>
                    <th class="px-4 py-3">Keperluan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Pengembalian</th>
                    <th class="px-4 py-3">Keterlambatan</th>
                </tr>
            </thead>
                <tbody class="text-slate-600">
                @foreach($transactions as $transaction)
                    <tr>
                        <td class="px-4 py-3">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $transaction->student?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $transaction->student?->student_number }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $transaction->laptop?->name }}</td>
                        <td class="px-4 py-3">{{ $transaction->usage_purpose }}</td>
                        <td class="px-4 py-3">
                            @if($transaction->status === 'borrowed')
                                <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-600">Dipinjam</span>
                            @elseif($transaction->was_late)
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Terlambat</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Dikembalikan</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $transaction->returned_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $transaction->late_minutes ?? 0 }} menit</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @if($transactions->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Tidak ada data pada rentang yang dipilih.</p>
    @endif
@endsection
