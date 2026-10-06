@extends('layouts.app')

@section('title', 'Dry Run Import Laptop')

@php
    $summary = $plan['summary'];
    $statusMeta = [
        'new' => ['label' => 'Baru', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'update' => ['label' => 'Update', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'unchanged' => ['label' => 'Tidak berubah', 'class' => 'bg-slate-50 text-slate-600 border-slate-200'],
        'error' => ['label' => 'Error', 'class' => 'bg-red-50 text-red-700 border-red-200'],
    ];
    $statusLabels = ['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Maintenance', 'retired' => 'Nonaktif'];
    $formatValue = function ($field, $value) use ($statusLabels) {
        if ($field === 'status') {
            return $statusLabels[$value] ?? ($value ?: '-');
        }
        return $value === null || $value === '' ? '-' : $value;
    };
    $importable = $summary['new'] + $summary['update'];
@endphp

@section('content')
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-4">
        <div>
            <a href="{{ route('admin.laptops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali ke Data Laptop</a>
            <h1 class="mt-1 text-xl font-semibold text-slate-800 m-0">Dry Run Import Laptop</h1>
            <p class="text-sm text-slate-500 mb-0">File: <span class="font-mono">{{ $filename }}</span> &middot; Belum ada data yang diubah.</p>
        </div>
    </div>

    <div class="grid gap-3 grid-cols-2 md:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Total baris</p>
            <p class="text-2xl font-bold text-slate-800 mb-0">{{ $summary['total'] }}</p>
        </div>
        @foreach (['new', 'update', 'unchanged', 'error'] as $key)
            <button type="button" data-filter="{{ $key }}" class="js-import-filter rounded-xl border p-4 text-left shadow-sm {{ $statusMeta[$key]['class'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1">{{ $statusMeta[$key]['label'] }}</p>
                <p class="text-2xl font-bold mb-0">{{ $summary[$key] }}</p>
            </button>
        @endforeach
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700 m-0">Detail per baris</h2>
            <button type="button" id="import-filter-reset" class="text-xs text-slate-500 hover:text-slate-800 underline">Tampilkan semua</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm text-sm mb-0" id="import-preview-table">
                <thead>
                    <tr class="text-xs uppercase text-slate-500">
                        <th>Baris</th>
                        <th>Kode / Serial</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plan['rows'] as $row)
                        <tr data-status="{{ $row['status'] }}">
                            <td class="font-mono text-xs text-slate-500">{{ $row['line'] }}</td>
                            <td class="font-mono text-xs">{{ $row['identifier'] }}</td>
                            <td>{{ $row['name'] ?? '-' }}</td>
                            <td>
                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusMeta[$row['status']]['class'] }}">
                                    {{ $statusMeta[$row['status']]['label'] }}
                                </span>
                            </td>
                            <td class="text-xs">
                                @if ($row['status'] === 'error')
                                    <ul class="list-disc pl-4 mb-0 text-red-600">
                                        @foreach ($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($row['status'] === 'update')
                                    <ul class="mb-0 pl-0 list-none space-y-0.5">
                                        @foreach ($row['changes'] as $field => [$old, $new])
                                            <li>
                                                <span class="font-semibold text-slate-600">{{ $labels[$field] ?? $field }}:</span>
                                                <span class="text-slate-400 line-through">{{ $formatValue($field, $old) }}</span>
                                                &rarr;
                                                <span class="text-blue-700">{{ $formatValue($field, $new) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif ($row['status'] === 'new')
                                    <span class="text-slate-500">Laptop akan ditambahkan.</span>
                                @else
                                    <span class="text-slate-400">Data sudah sama.</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form action="{{ route('admin.laptops.import.commit') }}" method="POST" class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            @csrf
            <div>
                @if ($summary['error'] > 0)
                    <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                        <input type="checkbox" name="abort_on_error" value="1">
                        Batalkan semua jika ada error
                    </label>
                    <p class="mt-2 mb-0 text-xs text-red-600">{{ $summary['error'] }} baris error akan dilewati kecuali opsi pembatalan dicentang.</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" form="import-cancel-form" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:border-slate-400">Batal</button>
                <button type="submit" id="import-commit-btn" @disabled($importable === 0) class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50">
                    Konfirmasi Import ({{ $summary['new'] }} baru, {{ $summary['update'] }} update)
                </button>
            </div>
        </form>
        <form id="import-cancel-form" action="{{ route('admin.laptops.import.cancel') }}" method="POST" hidden>@csrf</form>
    </div>

    <script>
        (function () {
            var rows = document.querySelectorAll('#import-preview-table tbody tr');
            function apply(status) {
                rows.forEach(function (tr) {
                    tr.style.display = !status || tr.dataset.status === status ? '' : 'none';
                });
            }
            document.querySelectorAll('.js-import-filter').forEach(function (btn) {
                btn.addEventListener('click', function () { apply(btn.dataset.filter); });
            });
            document.getElementById('import-filter-reset').addEventListener('click', function () { apply(null); });
        })();
    </script>
@endsection
