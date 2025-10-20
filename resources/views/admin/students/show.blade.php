@extends('layouts.app')

@section('title', 'Detail Siswa')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.students.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <h1 class="mt-1 text-xl font-semibold text-slate-800">{{ $student->name }}</h1>
            <p class="text-sm text-slate-500">{{ $student->student_number }} · {{ $student->classroom }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.students.qr', $student) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:border-slate-400">Cetak QR</a>
            <a href="{{ route('admin.students.edit', $student) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">Ubah Data</a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Profil</h2>
            <dl class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex justify-between">
                    <dt>Email</dt>
                    <dd>{{ $student->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>No. HP</dt>
                    <dd>{{ $student->phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Jenis Kelamin</dt>
                    <dd>{{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Kode Kartu</dt>
                    <dd class="font-mono text-xs text-slate-500">{{ $student->card_code ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Status Akun</dt>
                    <dd>
                        @if($student->is_active)
                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Aktif</span>
                        @else
                            <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Nonaktif</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt>Pelanggaran</dt>
                    <dd>{{ $student->violations_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Sanksi Aktif</dt>
                    <dd>
                        @if($student->sanction_ends_at)
                            <span class="text-amber-600">Hingga {{ $student->sanction_ends_at->translatedFormat('d M Y H:i') }}</span>
                        @else
                            <span class="text-slate-500">Tidak ada</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-600">Laptop Dimiliki</dt>
                    <dd class="mt-2 space-y-2">
                        @forelse($ownedLaptops as $laptop)
                            <a href="{{ route('admin.laptops.show', $laptop) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-xs hover:border-blue-200 hover:bg-blue-50">
                                <span class="font-semibold text-slate-700">{{ $laptop->name }}</span>
                                <span class="font-mono text-slate-500">{{ $laptop->code }}</span>
                            </a>
                        @empty
                            <span class="text-xs text-slate-500">Tidak ada laptop yang terdaftar sebagai milik siswa ini.</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Peminjaman Aktif</h2>
            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                @forelse($activeBorrowings as $transaction)
                    <li class="rounded-xl border border-blue-100 bg-blue-50/40 px-4 py-3">
                        <p class="font-semibold text-blue-700">{{ $transaction->laptop?->name }}</p>
                        <p class="text-xs text-blue-600">Kode: {{ $transaction->laptop?->code }}</p>
                        <p class="mt-1 text-xs text-blue-500">Jatuh tempo {{ $transaction->due_at?->translatedFormat('d M Y H:i') }}</p>
                    </li>
                @empty
                    <li class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada peminjaman aktif.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-700">Riwayat Peminjaman</h2>
        <div class="mt-4 table-responsive">
            <table class="table table-striped table-bordered table-sm datatable-default w-100">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="pb-2">Tanggal</th>
                        <th class="pb-2">Laptop</th>
                        <th class="pb-2">Keperluan</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Jatuh Tempo</th>
                        <th class="pb-2">Pengembalian</th>
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
                            <td class="py-2">{{ $transaction->due_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="py-2">{{ $transaction->returned_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
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
@endsection
