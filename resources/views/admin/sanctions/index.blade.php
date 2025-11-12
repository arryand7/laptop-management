@extends('layouts.app')

@section('title', 'Manajemen Sanksi')

@section('content')
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-slate-800">Manajemen Sanksi</h1>
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('admin.sanctions.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-blue-500">
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
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Alasan</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
                <tbody class="text-slate-600">
                @foreach($sanctions as $sanction)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $sanction->student?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $sanction->student?->student_number }} · {{ $sanction->student?->classroom }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $sanction->starts_at?->translatedFormat('d M Y') }} &rarr; {{ $sanction->ends_at?->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClasses = [
                                    'active' => 'bg-amber-100 text-amber-600',
                                    'expired' => 'bg-slate-200 text-slate-600',
                                    'revoked' => 'bg-rose-100 text-rose-600',
                                ];
                            @endphp
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$sanction->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($sanction->status) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $sanction->reason }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($sanction->status === 'active')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.sanctions.update', $sanction) }}" onsubmit="return confirm('Tandai sanksi sebagai selesai?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="expire">
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500">Selesai</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sanctions.update', $sanction) }}" onsubmit="return confirm('Cabut sanksi ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="revoke">
                                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500">Cabut</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Tidak ada aksi.</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @if($sanctions->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Tidak ada data sanksi.</p>
    @endif
@endsection
