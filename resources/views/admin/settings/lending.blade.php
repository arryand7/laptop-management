@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi - Peraturan Laptop')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Peraturan Batas Pengembalian</h1>
                <p class="mt-1 text-sm text-slate-500">Atur batas waktu default pengembalian yang akan otomatis terisi saat peminjaman dibuat.</p>
            </div>
            <div class="flex gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('admin.settings.application') }}" class="text-slate-500 hover:text-slate-700">← Identitas</a>
                <a href="{{ route('admin.settings.mail') }}" class="text-blue-600 hover:text-blue-500">Pengaturan Email →</a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.lending.update') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            @php $mode = old('lending_due_mode', $setting->lending_due_mode ?? 'relative'); @endphp

            <div class="space-y-4">
                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="radio" name="lending_due_mode" value="relative" @checked($mode === 'relative') class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                    <span>Tambah beberapa hari dari waktu peminjaman</span>
                </label>
                <div class="ml-7 space-y-3 lending-config" data-mode="relative">
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Jumlah hari</label>
                        <input type="number" name="lending_due_days" value="{{ old('lending_due_days', $setting->lending_due_days ?? 1) }}" min="1" max="30" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('lending_due_days') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Jam pengembalian (opsional)</label>
                        <input type="time" name="lending_due_time" value="{{ old('lending_due_time', $setting->lending_due_time) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('lending_due_time') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="radio" name="lending_due_mode" value="daily" @checked($mode === 'daily') class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                    <span>Setiap hari pada jam tertentu</span>
                </label>
                <div class="ml-7 space-y-3 lending-config" data-mode="daily">
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Jam pengembalian</label>
                        <input type="time" name="lending_due_time_daily" value="{{ old('lending_due_time', $setting->lending_due_time) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="radio" name="lending_due_mode" value="fixed" @checked($mode === 'fixed') class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                    <span>Tanggal &amp; waktu khusus</span>
                </label>
                <div class="ml-7 space-y-3 lending-config" data-mode="fixed">
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Tanggal &amp; waktu pengembalian</label>
                        <input type="datetime-local" name="lending_due_date" value="{{ old('lending_due_date', optional($setting->lending_due_date ? \Illuminate\Support\Carbon::parse($setting->lending_due_date) : null)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('lending_due_date') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const configs = document.querySelectorAll('.lending-config');

        const syncConfig = () => {
            const value = document.querySelector('input[name="lending_due_mode"]:checked')?.value;
            configs.forEach((config) => {
                const active = config.dataset.mode === value;
                config.style.display = active ? 'block' : 'none';
                config.querySelectorAll('input, select').forEach((field) => {
                    field.disabled = !active;
                });
            });
        };

        document.querySelectorAll('input[name="lending_due_mode"]').forEach((radio) => {
            radio.addEventListener('change', syncConfig);
        });

        syncConfig();
    </script>
@endpush
