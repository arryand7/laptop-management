@extends('layouts.app')

@section('title', 'Detail Laptop')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.laptops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <h1 class="mt-1 text-xl font-semibold text-slate-800">{{ $laptop->name }}</h1>
            <p class="text-sm text-slate-500">Kode {{ $laptop->code }} · {{ $laptop->brand }} {{ $laptop->model }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.laptops.qr', $laptop) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:border-slate-400">Cetak QR</a>
            <a href="{{ route('admin.laptops.edit', $laptop) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">Ubah Data</a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Informasi Umum</h2>
            <dl class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex justify-between">
                    <dt>Serial Number</dt>
                    <dd>{{ $laptop->serial_number ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt>Pemilik</dt>
                    <dd>
                        @if($laptop->owner)
                            <a href="{{ route('admin.students.show', $laptop->owner) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                {{ $laptop->owner->name }}
                                <span class="ml-1 text-xs text-slate-500">{{ $laptop->owner->student_number }}</span>
                            </a>
                        @else
                            <span class="text-slate-500">Belum ditetapkan</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt>Status</dt>
                    <dd>
                        @php
                            $statusClasses = [
                                'available' => 'bg-emerald-100 text-emerald-600',
                                'borrowed' => 'bg-blue-100 text-blue-600',
                                'maintenance' => 'bg-amber-100 text-amber-600',
                                'retired' => 'bg-slate-200 text-slate-600',
                            ];
                        @endphp
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$laptop->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($laptop->status) }}</span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt>Terakhir Dicek</dt>
                    <dd>{{ $laptop->last_checked_at?->translatedFormat('d M Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-600">Catatan</dt>
                    <dd class="mt-1">{{ $laptop->notes ?? 'Tidak ada' }}</dd>
                </div>
            </dl>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Spesifikasi</h2>
            <dl class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex justify-between"><dt>CPU</dt><dd>{{ data_get($laptop->specifications, 'cpu', '-') }}</dd></div>
                <div class="flex justify-between"><dt>RAM</dt><dd>{{ data_get($laptop->specifications, 'ram', '-') }}</dd></div>
                <div class="flex justify-between"><dt>Storage</dt><dd>{{ data_get($laptop->specifications, 'storage', '-') }}</dd></div>
                <div class="flex justify-between"><dt>OS</dt><dd>{{ data_get($laptop->specifications, 'os', '-') }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-700">Status Peminjaman</h2>
        @if($activeBorrow)
            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                <p class="font-semibold">Sedang dipinjam oleh {{ $activeBorrow->student?->name }}</p>
                <p class="text-xs">Jatuh tempo {{ $activeBorrow->due_at?->translatedFormat('d M Y H:i') }}</p>
                <p class="text-xs text-blue-500">Keperluan: {{ $activeBorrow->usage_purpose }}</p>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-500">Laptop ini tidak memiliki peminjaman aktif.</p>
        @endif
    </section>
@endsection
