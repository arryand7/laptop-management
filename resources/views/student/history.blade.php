@extends('layouts.app')

@section('title', 'Riwayat Peminjaman')

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-xl font-semibold text-slate-800">Riwayat Peminjaman Saya</h1>
            <p class="text-sm text-slate-500">Pantau status peminjaman dan pelanggaran Anda.</p>
        </header>

        <section class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Peminjaman Aktif</h2>
                <ul class="mt-4 space-y-3">
                    @forelse($activeBorrowings as $transaction)
                        <li class="rounded-xl border border-blue-100 bg-blue-50/40 px-4 py-3 text-sm text-blue-700">
                            <p class="font-semibold">{{ $transaction->laptop?->name }} ({{ $transaction->laptop?->code }})</p>
                            <p class="text-xs">Jatuh tempo {{ $transaction->due_at?->translatedFormat('d M Y H:i') }}</p>
                            <p class="mt-1 text-xs text-blue-500">Keperluan: {{ $transaction->usage_purpose }}</p>
                        </li>
                    @empty
                        <li class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada peminjaman aktif.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Status Pelanggaran</h2>
                <p class="text-3xl font-semibold text-slate-800">{{ $student->violations_count }}</p>
                <p class="text-sm text-slate-500">Total pelanggaran keterlambatan.
                    @if($student->sanction_ends_at)
                        <span class="text-amber-600">Sanksi aktif hingga {{ $student->sanction_ends_at->translatedFormat('d M Y H:i') }}</span>
                    @else
                        <span class="text-emerald-600">Tidak ada sanksi aktif.</span>
                    @endif
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Riwayat Lengkap</h2>
            <div class="mt-4 table-responsive">
                <table class="table table-striped table-bordered table-sm datatable-default w-100">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="pb-2">Tanggal Pinjam</th>
                            <th class="pb-2">Laptop</th>
                            <th class="pb-2">Keperluan</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Pengembalian</th>
                            <th class="pb-2">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600">
                        @forelse($history as $transaction)
                            <tr>
                                <td class="py-2">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="py-2">{{ $transaction->laptop?->name }}</td>
                                <td class="py-2">{{ $transaction->usage_purpose }}</td>
                                <td class="py-2">
                                    @if($transaction->status === 'borrowed')
                                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-600">Dipinjam</span>
                                    @elseif($transaction->was_late)
                                        <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Terlambat</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Dikembalikan</span>
                                    @endif
                                </td>
                                <td class="py-2">{{ $transaction->returned_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="py-2">{{ $transaction->staff?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">Belum ada riwayat peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
