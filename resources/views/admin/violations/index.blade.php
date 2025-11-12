@extends('layouts.app')

@section('title', 'Pelanggaran Siswa')

@section('content')
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-slate-800">Pelanggaran Peminjaman</h1>
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('admin.violations.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-blue-500">
                <i class="fas fa-plus mr-2"></i> Tambah Data
            </a>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm datatable-default w-100">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Siswa</th>
                    <th class="px-4 py-3">Transaksi</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Catatan</th>
                    <th class="px-4 py-3">Poin</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
                <tbody class="text-slate-600">
                @foreach($violations as $violation)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $violation->student?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $violation->student?->student_number }} · {{ $violation->student?->classroom }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-slate-500">{{ $violation->transaction?->transaction_code }}</td>
                        <td class="px-4 py-3">{{ $violation->occurred_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $violation->notes }}</td>
                        <td class="px-4 py-3">{{ $violation->points }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($violation->status === 'active')
                                <form action="{{ route('admin.violations.update', $violation) }}" method="POST" onsubmit="return confirm('Tandai pelanggaran ini sudah ditangani?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500">Tandai Selesai</button>
                                </form>
                            @else
                                <span class="text-xs font-semibold text-slate-500">Selesai {{ $violation->resolved_at?->translatedFormat('d M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @if($violations->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Tidak ada data pelanggaran.</p>
    @endif
@endsection
