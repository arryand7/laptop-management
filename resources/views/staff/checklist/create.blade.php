@extends('layouts.app')

@section('title', 'Laptop Rack Checklist')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Laptop Rack Checklist</h1>
                <p class="mt-1 text-sm text-slate-500">Periksa dan pastikan seluruh laptop kembali ke rak. Hilangkan centang pada laptop yang tidak ada.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('staff.checklist.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-sync-alt text-sm text-blue-500"></i> Mulai Checklist Baru
                </a>
                <a href="{{ route('staff.checklist.history') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-history text-sm text-blue-500"></i> Riwayat Checklist
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('staff.checklist.store') }}" method="POST" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            @php
                $selected = collect(old('found_laptops', $laptops->pluck('id')->all()))->map(fn ($id) => (int) $id);
                $initialFound = $laptops->filter(fn ($laptop) => $laptop->status !== 'borrowed' && $selected->contains($laptop->id))->count();
                $initialMissing = $laptops->filter(fn ($laptop) => $laptop->status !== 'borrowed' && !$selected->contains($laptop->id))->count();
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2">Ada?</th>
                            <th class="px-3 py-2">Kode Laptop</th>
                            <th class="px-3 py-2">Nama &amp; Pemilik</th>
                            <th class="px-3 py-2">Status Sistem</th>
                            <th class="px-3 py-2">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($laptops as $index => $laptop)
                            <tr class="@if($index % 2 === 0) bg-white @else bg-slate-50/60 @endif">
                                <td class="px-3 py-3 text-xs font-medium text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-3">
                                    <label class="relative inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            name="found_laptops[]"
                                            value="{{ $laptop->id }}"
                                            class="js-checklist-checkbox h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            data-status="{{ $laptop->status }}"
                                            @checked($selected->contains($laptop->id))
                                        >
                                    </label>
                                </td>
                                <td class="px-3 py-3 font-mono text-sm font-semibold text-slate-700">{{ $laptop->code }}</td>
                                <td class="px-3 py-3 text-sm">
                                    <div class="font-semibold text-slate-800">{{ $laptop->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        @if($laptop->owner)
                                            {{ $laptop->owner->name }} ({{ $laptop->owner->student_number ?? '-' }})
                                        @else
                                            Tidak terikat ke siswa
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-xs font-semibold">
                                    @php
                                        $statusClasses = [
                                            'available' => 'bg-emerald-100 text-emerald-700',
                                            'borrowed' => 'bg-amber-100 text-amber-700',
                                            'maintenance' => 'bg-blue-100 text-blue-700',
                                            'retired' => 'bg-slate-200 text-slate-700',
                                        ];
                                        $statusLabel = [
                                            'available' => 'Available',
                                            'borrowed' => 'Borrowed',
                                            'maintenance' => 'Maintenance',
                                            'retired' => 'Retired',
                                        ][$laptop->status] ?? ucfirst($laptop->status);
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-3 py-1 {{ $statusClasses[$laptop->status] ?? 'bg-slate-200 text-slate-700' }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if($laptop->is_missing)
                                        <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">
                                            <i class="fas fa-exclamation-circle"></i> Dilaporkan hilang
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-500">
                                    @if($laptop->status === 'borrowed')
                                        Sedang dipinjam
                                    @elseif($laptop->is_missing)
                                        Mohon verifikasi fisik di lapangan.
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Total Laptop</p>
                    <p id="summary-total" class="mt-1 text-2xl font-semibold text-slate-800">{{ $totalCount }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <p class="text-xs uppercase tracking-wide">Ditemukan</p>
                    <p id="summary-found" class="mt-1 text-2xl font-semibold">{{ $initialFound }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="text-xs uppercase tracking-wide">Hilang</p>
                    <p id="summary-missing" class="mt-1 text-2xl font-semibold">{{ $initialMissing }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <p class="text-xs uppercase tracking-wide">Sedang Dipinjam</p>
                    <p id="summary-borrowed" class="mt-1 text-2xl font-semibold">{{ $initialBorrowedCount }}</p>
                </div>
            </div>

            <div>
                <label for="note" class="block text-sm font-semibold text-slate-600">Catatan (opsional)</label>
                <textarea id="note" name="note" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('note') }}</textarea>
                @error('note') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <span class="text-xs text-slate-400">Terakhir checklist: {{ optional(optional($recentSession)->completed_at)->diffForHumans() ?? 'belum pernah' }}</span>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <i class="fas fa-save text-base"></i>
                    Simpan Checklist
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const total = {{ $totalCount }};
            const borrowedBase = {{ $initialBorrowedCount }};
            const checkboxes = Array.from(document.querySelectorAll('.js-checklist-checkbox'));
            const summaryTotal = document.getElementById('summary-total');
            const summaryFound = document.getElementById('summary-found');
            const summaryMissing = document.getElementById('summary-missing');
            const summaryBorrowed = document.getElementById('summary-borrowed');

            const updateSummary = () => {
                let found = 0;
                let missing = 0;

                checkboxes.forEach((checkbox) => {
                    const status = checkbox.dataset.status;
                    if (status === 'borrowed') {
                        return;
                    }
                    if (checkbox.checked) {
                        found += 1;
                    } else {
                        missing += 1;
                    }
                });

                summaryTotal.textContent = total;
                summaryFound.textContent = found;
                summaryMissing.textContent = missing;
                summaryBorrowed.textContent = borrowedBase;
            };

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSummary);
            });

            updateSummary();
        });
    </script>
@endpush
