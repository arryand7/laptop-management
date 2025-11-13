@extends('layouts.app')

@section('title', 'Tambah Laptop Baru')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Tambah Laptop Baru</h1>
                <p class="mt-1 text-sm text-slate-500">Laptop akan tersimpan setelah diverifikasi admin. Isi data dengan lengkap.</p>
            </div>
            <a href="{{ route('student.laptops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
        </div>

        <form action="{{ route('student.laptops.store') }}" method="POST" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-600">Nama Laptop</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('name') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brand" class="block text-sm font-semibold text-slate-600">Merek</label>
                    <input type="text" id="brand" name="brand" value="{{ old('brand') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('brand') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="model" class="block text-sm font-semibold text-slate-600">Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('model') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="serial_number" class="block text-sm font-semibold text-slate-600">Kode Laptop (Data dari admin)</label>
                    <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('serial_number') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold text-slate-600">Catatan</label>
                <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('notes') }}</textarea>
                @error('notes') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="spec_cpu" class="block text-sm font-semibold text-slate-600">CPU</label>
                    <input type="text" id="spec_cpu" name="spec_cpu" value="{{ old('spec_cpu') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('spec_cpu') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="spec_ram" class="block text-sm font-semibold text-slate-600">RAM</label>
                    <input type="text" id="spec_ram" name="spec_ram" value="{{ old('spec_ram') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('spec_ram') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="spec_storage" class="block text-sm font-semibold text-slate-600">Storage</label>
                    <input type="text" id="spec_storage" name="spec_storage" value="{{ old('spec_storage') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('spec_storage') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="spec_os" class="block text-sm font-semibold text-slate-600">Sistem Operasi</label>
                    <input type="text" id="spec_os" name="spec_os" value="{{ old('spec_os') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('spec_os') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="reset" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Kirim Permintaan</button>
            </div>
        </form>
    </div>
@endsection
