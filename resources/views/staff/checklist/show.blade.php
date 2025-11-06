@extends('layouts.app')

@section('title', 'Detail Checklist Laptop')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Detail Checklist Laptop</h1>
                <p class="mt-1 text-sm text-slate-500">Ringkasan checklist yang dilakukan oleh {{ $session->staff?->name ?? '—' }} pada {{ $session->completed_at?->translatedFormat('d M Y H:i') ?? '-' }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('staff.checklist.history') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-arrow-left text-blue-500"></i> Kembali ke Riwayat
                </a>
                <a href="{{ route('staff.checklist.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-plus-circle text-blue-500"></i> Checklist Baru
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
                <div class="mt-2 text-xs text-emerald-600">
                    📦 Ditemukan: {{ $session->found_count }} |
                    ⚠️ Hilang: {{ $session->missing_count }} |
                    🕓 Dipinjam: {{ $session->borrowed_count }}
                </div>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p class="text-xs uppercase tracking-wide text-slate-400">Total Laptop</p>
                <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $session->total_laptops }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <p class="text-xs uppercase tracking-wide">Ditemukan</p>
                <p class="mt-1 text-2xl font-semibold">{{ $session->found_count }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="text-xs uppercase tracking-wide">Hilang</p>
                <p class="mt-1 text-2xl font-semibold">{{ $session->missing_count }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                <p class="text-xs uppercase tracking-wide">Sedang Dipinjam</p>
                <p class="mt-1 text-2xl font-semibold">{{ $session->borrowed_count }}</p>
            </div>
        </div>

        @if($session->note)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan Petugas</p>
                <p class="mt-1">{{ $session->note }}</p>
            </div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-700">Rincian Laptop</h2>

            <div class="mt-4 grid gap-6 lg:grid-cols-3">
                <div>
                    <h3 class="text-sm font-semibold text-emerald-600">Ditemukan ({{ $foundDetails->count() }})</h3>
                    <div class="mt-2 space-y-2 text-sm text-slate-600">
                        @forelse($foundDetails as $detail)
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2">
                                <div class="font-semibold text-slate-800">{{ $detail->laptop->code }}</div>
                                <div class="text-xs text-slate-500">{{ $detail->laptop->name }}</div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Tidak ada data.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-rose-600">Hilang ({{ $missingDetails->count() }})</h3>
                    <div class="mt-2 space-y-2 text-sm text-rose-700">
                        @forelse($missingDetails as $detail)
                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
                                <div class="font-semibold text-rose-700">{{ $detail->laptop->code }}</div>
                                <div class="text-xs text-rose-500">{{ $detail->laptop->name }}</div>
                                @if($detail->laptop->owner)
                                    <div class="text-xs text-rose-500">Pemilik: {{ $detail->laptop->owner->name }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Tidak ada laptop yang hilang dilaporkan.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-amber-600">Sedang Dipinjam ({{ $borrowedDetails->count() }})</h3>
                    <div class="mt-2 space-y-2 text-sm text-amber-700">
                        @forelse($borrowedDetails as $detail)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                <div class="font-semibold text-amber-700">{{ $detail->laptop->code }}</div>
                                <div class="text-xs text-amber-500">{{ $detail->laptop->name }}</div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Tidak ada laptop yang dipinjam.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
