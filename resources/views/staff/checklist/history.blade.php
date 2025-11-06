@extends('layouts.app')

@section('title', 'Riwayat Checklist Laptop')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Riwayat Checklist Laptop</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau catatan checklist yang pernah dilakukan beserta ringkasannya.</p>
            </div>
            <a href="{{ route('staff.checklist.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold uppercase text-slate-600 hover:bg-slate-50">
                <i class="fas fa-plus-circle text-blue-500"></i> Checklist Baru
            </a>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Waktu</th>
                            <th class="px-3 py-2">Petugas</th>
                            <th class="px-3 py-2 text-center">Total</th>
                            <th class="px-3 py-2 text-center text-emerald-600">Ditemukan</th>
                            <th class="px-3 py-2 text-center text-rose-600">Hilang</th>
                            <th class="px-3 py-2 text-center text-amber-600">Dipinjam</th>
                            <th class="px-3 py-2">Catatan</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($sessions as $session)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 text-sm">
                                    <div class="font-semibold text-slate-800">{{ $session->started_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $session->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-3 py-3 text-sm">{{ $session->staff?->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-slate-700">{{ $session->total_laptops }}</td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-emerald-600">{{ $session->found_count }}</td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-rose-600">{{ $session->missing_count }}</td>
                                <td class="px-3 py-3 text-center text-sm font-semibold text-amber-600">{{ $session->borrowed_count }}</td>
                                <td class="px-3 py-3 text-xs text-slate-500">{{ $session->note ? \Illuminate\Support\Str::limit($session->note, 60) : '—' }}</td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('staff.checklist.show', $session) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold uppercase text-slate-600 hover:bg-slate-50">
                                        <i class="fas fa-eye text-blue-500"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">Belum ada checklist yang terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
@endsection
