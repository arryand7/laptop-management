@extends('layouts.app')

@section('title', 'Edit Checklist Laptop')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Edit Checklist Laptop</h1>
                <p class="mt-1 text-sm text-slate-500">Checklist #{{ $session->id }} oleh {{ $session->staff?->name ?? '—' }} pada {{ $session->started_at?->translatedFormat('d M Y H:i') ?? '-' }}.</p>
            </div>
            <a href="{{ route('staff.checklist.history') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold uppercase text-slate-600 hover:bg-slate-50">
                <i class="fas fa-arrow-left text-blue-500"></i> Kembali
            </a>
        </div>

        <form action="{{ route('staff.checklist.update', $session) }}" method="POST" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            @if($laptops->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    Checklist ini tidak memiliki data laptop untuk diedit.
                </div>
            @else
                @php
                    $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan'];
                    $initialFound = $laptops->filter(fn ($laptop) => $foundIds->contains($laptop->id))->count();
                @endphp

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-slate-500">Mengedit {{ $laptops->count() }} laptop pada checklist ini.</div>
                    <div class="w-full max-w-xs">
                        <label for="checklist-search" class="sr-only">Cari laptop</label>
                        <input type="search" id="checklist-search" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Cari kode, nama, atau pemilik...">
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase text-slate-500">
                        <label for="checklist-length" class="text-[0.7rem]">Tampilkan</label>
                        <select id="checklist-length" class="rounded-lg border border-slate-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="10">10 data</option>
                            <option value="25" selected>25 data</option>
                            <option value="50">50 data</option>
                            <option value="100">100 data</option>
                            <option value="-1">Semua data</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table id="checklist-table" class="table table-striped table-bordered table-sm datatable-default w-100">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2">Ada?</th>
                            <th class="px-3 py-2">Kode Laptop</th>
                            <th class="px-3 py-2">Nama Laptop</th>
                            <th class="px-3 py-2">Pemilik</th>
                            <th class="px-3 py-2">Kelas</th>
                            <th class="px-3 py-2">Gender</th>
                            <th class="px-3 py-2">Status</th>
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
                                            @checked($foundIds->contains($laptop->id) || $laptop->status === 'borrowed')
                                            @disabled($laptop->status === 'borrowed')
                                        >
                                    </label>
                                </td>
                                <td class="px-3 py-3 font-mono text-sm font-semibold text-slate-700">{{ $laptop->code }}</td>
                                <td class="px-3 py-3 text-sm font-semibold text-slate-800">{{ $laptop->name }}</td>
                                <td class="px-3 py-3 text-sm text-slate-600">
                                    @if($laptop->owner)
                                        <div class="font-semibold text-slate-700">{{ $laptop->owner->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $laptop->owner->student_number ?? '-' }}</div>
                                    @else
                                        <span class="text-xs text-slate-400">Tidak terikat</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-slate-600">{{ $laptop->owner?->classroom ?? '—' }}</td>
                                <td class="px-3 py-3 text-sm text-slate-600">{{ $genderLabels[$laptop->owner?->gender] ?? ucfirst($laptop->owner?->gender ?? '—') }}</td>
                                <td class="px-3 py-3 text-xs font-semibold">
                                    @php
                                        $statusClasses = [
                                            'available' => 'bg-emerald-100 text-emerald-700',
                                            'borrowed' => 'bg-amber-100 text-amber-700',
                                            'maintenance' => 'bg-blue-100 text-blue-700',
                                            'retired' => 'bg-slate-200 text-slate-600',
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
                        <p id="summary-total" class="mt-1 text-2xl font-semibold text-slate-800">{{ $laptops->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <p class="text-xs uppercase tracking-wide">Ditemukan</p>
                        <p id="summary-found" class="mt-1 text-2xl font-semibold">{{ $initialFound }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="text-xs uppercase tracking-wide">Hilang</p>
                        <p id="summary-missing" class="mt-1 text-2xl font-semibold">{{ $session->missing_count }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        <p class="text-xs uppercase tracking-wide">Sedang Dipinjam</p>
                        <p id="summary-borrowed" class="mt-1 text-2xl font-semibold">{{ $borrowedCount }}</p>
                    </div>
                </div>
            @endif

            <div>
                <label for="note" class="block text-sm font-semibold text-slate-600">Catatan (opsional)</label>
                <textarea id="note" name="note" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('note', $session->note) }}</textarea>
                @error('note') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            @if($laptops->isNotEmpty())
            <div class="flex items-center justify-end gap-3">
                <span class="text-xs text-slate-400">Checklist dibuat: {{ $session->created_at->diffForHumans() }}</span>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <i class="fas fa-save text-base"></i>
                    Simpan Perubahan
                </button>
            </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const total = {{ $laptops->count() }};
            const borrowedBase = {{ $borrowedCount }};
            const summaryTotal = document.getElementById('summary-total');
            const summaryFound = document.getElementById('summary-found');
            const summaryMissing = document.getElementById('summary-missing');
            const summaryBorrowed = document.getElementById('summary-borrowed');

            const getCheckboxes = () => Array.from(document.querySelectorAll('.js-checklist-checkbox'));

            const updateSummary = () => {
                let found = 0;
                let missing = 0;

                getCheckboxes().forEach((checkbox) => {
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

            document.addEventListener('change', (event) => {
                if (event.target.classList.contains('js-checklist-checkbox')) {
                    updateSummary();
                }
            });

            const tableElement = window.jQuery ? window.jQuery('#checklist-table') : null;
            let dataTable = null;

            if (tableElement && tableElement.length) {
                dataTable = tableElement.DataTable({
                    paging: true,
                    order: [[2, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 1] },
                        { searchable: false, targets: [0, 1] },
                    ],
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                });

                dataTable.on('draw', () => {
                    setTimeout(updateSummary, 0);
                });
            }

            const searchInput = document.getElementById('checklist-search');
            const lengthSelect = document.getElementById('checklist-length');

            if (searchInput && dataTable) {
                searchInput.addEventListener('input', (event) => {
                    dataTable.search(event.target.value).draw();
                });
            }

            if (lengthSelect && dataTable) {
                lengthSelect.addEventListener('change', (event) => {
                    dataTable.page.len(Number(event.target.value)).draw();
                });
            }

            updateSummary();
        });
    </script>
@endpush
