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

        <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-4">
                @php
                    $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan'];
                    $filters = $selectedFilters ?? ['classrooms' => [], 'gender' => null, 'status' => null];
                @endphp
                <div class="md:col-span-2">
                    <label for="filter-classrooms" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Kelas (dapat pilih lebih dari satu)</label>
                    <select id="filter-classrooms" name="classrooms[]" multiple class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom }}" @selected(in_array($classroom, $filters['classrooms'], true))>{{ $classroom }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Gunakan CTRL/CMD + klik untuk memilih lebih dari satu kelas.</p>
                </div>
                <div>
                    <label for="filter-gender" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Kelamin</label>
                    <select id="filter-gender" name="gender" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Semua</option>
                        @foreach($genders as $gender)
                            <option value="{{ $gender }}" @selected($filters['gender'] === $gender)>{{ $genderLabels[$gender] ?? ucfirst($gender) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status Sistem</label>
                    <select id="filter-status" name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Semua Status</option>
                        <option value="available" @selected($filters['status'] === 'available')>Available</option>
                        <option value="borrowed" @selected($filters['status'] === 'borrowed')>Borrowed</option>
                        <option value="maintenance" @selected($filters['status'] === 'maintenance')>Maintenance</option>
                        <option value="retired" @selected($filters['status'] === 'retired')>Retired</option>
                        <option value="missing" @selected($filters['status'] === 'missing')>Dilaporkan hilang</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex flex-wrap items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('staff.checklist.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                    <span class="text-xs text-slate-500">Menampilkan {{ $totalCount }} laptop sesuai filter.</span>
                </div>
            </form>
        </div>

        <form action="{{ route('staff.checklist.store') }}" method="POST" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @foreach($filters['classrooms'] as $classroom)
                <input type="hidden" name="classrooms[]" value="{{ $classroom }}">
            @endforeach
            <input type="hidden" name="gender" value="{{ $filters['gender'] }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">

            @php
                $selected = collect(old('found_laptops', $laptops->pluck('id')->all()))->map(fn ($id) => (int) $id);
                $initialFound = $laptops->filter(fn ($laptop) => $laptop->status !== 'borrowed' && $selected->contains($laptop->id))->count();
                $initialMissing = $laptops->filter(fn ($laptop) => $laptop->status !== 'borrowed' && !$selected->contains($laptop->id))->count();
            @endphp

            @if($laptops->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    Tidak ada laptop yang cocok dengan filter. Silakan atur ulang filter atau pilih kelas/jenis kelamin lain sebelum melakukan checklist.
                </div>
            @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-slate-500">Menampilkan {{ $totalCount }} laptop pada checklist ini.</div>
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
                                            @checked($selected->contains($laptop->id))
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

            @php
                $nonBorrowedCount = $laptops->where('status', 'borrowed')->count();
                $initialFound = $laptops->count() - $nonBorrowedCount;
            @endphp

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Total Laptop</p>
                    <p id="summary-total" class="mt-1 text-2xl font-semibold text-slate-800">{{ $totalCount }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <p class="text-xs uppercase tracking-wide">Ditemukan</p>
                    <p id="summary-found" class="mt-1 text-2xl font-semibold">{{ max($initialFound, 0) }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="text-xs uppercase tracking-wide">Hilang</p>
                    <p id="summary-missing" class="mt-1 text-2xl font-semibold">0</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <p class="text-xs uppercase tracking-wide">Sedang Dipinjam</p>
                    <p id="summary-borrowed" class="mt-1 text-2xl font-semibold">{{ $initialBorrowedCount }}</p>
                </div>
            </div>
            @endif

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
            const statusFilter = document.getElementById('checklist-status-filter');
            const classFilter = document.getElementById('checklist-class-filter');
            const genderFilter = document.getElementById('checklist-gender-filter');

            if (dataTable) {
                if (searchInput) {
                    searchInput.addEventListener('input', (event) => {
                        dataTable.search(event.target.value).draw();
                    });
                }

            if (lengthSelect && dataTable) {
                lengthSelect.addEventListener('change', (event) => {
                    const value = Number(event.target.value);
                    dataTable.page.len(value).draw();
                });
            }

            if (statusFilter && dataTable) {
                const statusMap = {
                    available: 'Available',
                        borrowed: 'Borrowed',
                        maintenance: 'Maintenance',
                        retired: 'Retired',
                        missing: 'Dilaporkan hilang',
                    };

                    statusFilter.addEventListener('change', (event) => {
                        const value = event.target.value;
                        if (!value) {
                            dataTable.column(7).search('').draw();
                            return;
                        }
                        const label = statusMap[value] ?? value;
                        dataTable.column(7).search(label, true, false).draw();
                    });
                }

            if (classFilter && dataTable) {
                classFilter.addEventListener('change', (event) => {
                        const value = event.target.value;
                        dataTable.column(5).search(value || '', true, false).draw();
                    });
                }

            if (genderFilter && dataTable) {
                genderFilter.addEventListener('change', (event) => {
                        const value = event.target.value;
                        dataTable.column(6).search(value || '', true, false).draw();
                    });
                }
            }

            updateSummary();
        });
    </script>
@endpush
