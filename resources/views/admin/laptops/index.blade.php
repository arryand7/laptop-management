@extends('layouts.app')

@section('title', 'Data Laptop')

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-slate-800">Data Laptop</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.laptops.template') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                <i class="fas fa-file-download"></i> Template Import
            </a>
            <form action="{{ route('admin.laptops.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-sm">
                @csrf
                <label class="font-semibold text-slate-600 mb-0">Import Excel</label>
                <input type="file" name="file" class="text-xs" accept=".xlsx,.xls" required>
                <button type="submit" class="rounded bg-slate-900 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700">Upload</button>
            </form>
        </div>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari Laptop</label>
            <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Nama, kode, brand" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="">Semua</option>
                @foreach(['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Maintenance', 'retired' => 'Nonaktif'] as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
        </div>
    </form>

    @php $laptopsFormId = 'laptops-bulk-form'; @endphp
    <form id="{{ $laptopsFormId }}" class="js-bulk-form" action="{{ route('admin.laptops.bulk') }}" method="POST" data-table="#laptops-table" hidden>
        @csrf
    </form>
    <div class="mt-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <select name="action" class="js-bulk-action rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required data-confirm-message="Yakin ingin menghapus laptop terpilih?" data-confirm-action="delete" data-toggle-target="#bulk-status-wrapper" form="{{ $laptopsFormId }}">
                <option value="">Pilih aksi</option>
                <option value="status">Ubah Status</option>
                <option value="delete">Hapus</option>
                <option value="print_qr">Cetak QR</option>
            </select>
            <div id="bulk-status-wrapper">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" form="{{ $laptopsFormId }}">
                    <option value="">Pilih status baru</option>
                    @foreach(['available' => 'Tersedia', 'maintenance' => 'Maintenance', 'retired' => 'Nonaktif', 'borrowed' => 'Dipinjam'] as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" form="{{ $laptopsFormId }}">
                <i class="fas fa-check"></i> Terapkan
            </button>
        </div>
        <a href="{{ route('admin.laptops.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold shadow hover:bg-blue-400">
            <i class="fas fa-plus"></i> Tambah Data
        </a>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table id="laptops-table" class="table table-striped table-bordered table-sm datatable-default w-100">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="select-checkbox px-4 py-3 text-center align-middle" data-orderable="false">
                        <input type="checkbox" class="js-select-all" data-target="#laptops-table">
                    </th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Brand / Model</th>
                    <th class="px-4 py-3">Pemilik</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Terakhir Dicek</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-slate-600">
                @foreach($laptops as $laptop)
                    <tr>
                        <td class="px-4 py-3 text-center align-middle">
                            <input type="checkbox" class="js-row-checkbox" name="laptop_ids[]" value="{{ $laptop->id }}" form="{{ $laptopsFormId }}">
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $laptop->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $laptop->name }}</td>
                        <td class="px-4 py-3">{{ $laptop->brand }} {{ $laptop->model }}</td>
                        <td class="px-4 py-3">
                            @if($laptop->owner)
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-700">{{ $laptop->owner->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $laptop->owner->student_number }} · {{ $laptop->owner->classroom }}</span>
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClasses = [
                                    'available' => 'bg-emerald-100 text-emerald-600',
                                    'borrowed' => 'bg-blue-100 text-blue-600',
                                    'maintenance' => 'bg-amber-100 text-amber-600',
                                    'retired' => 'bg-slate-200 text-slate-600',
                                ];
                            @endphp
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$laptop->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($laptop->status) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $laptop->last_checked_at?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.laptops.show', $laptop) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Detail</a>
                                <a href="{{ route('admin.laptops.edit', $laptop) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-500">Ubah</a>
                                <a href="{{ route('admin.laptops.qr', $laptop) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-500">QR</a>
                                <form action="{{ route('admin.laptops.destroy', $laptop) }}" method="POST" onsubmit="return confirm('Hapus data laptop ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-500">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($laptops->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Belum ada data laptop.</p>
    @endif
@endsection
