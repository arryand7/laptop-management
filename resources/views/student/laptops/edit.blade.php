@extends('layouts.app')

@section('title', 'Perbarui Data Laptop')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('student.laptops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $laptop->code }}
            </span>
        </div>

        <div>
            <h1 class="text-xl font-semibold text-slate-800">Ajukan Perubahan Data Laptop</h1>
            <p class="text-sm text-slate-500">Setiap perubahan akan diperiksa dan harus disetujui oleh admin sebelum diterapkan.</p>
        </div>

        @if($pendingRequest)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Permintaan Anda sebelumnya sedang menunggu persetujuan admin
                (diajukan {{ $pendingRequest->created_at?->diffForHumans() }}). Pengajuan baru akan menggantikan permintaan tersebut.
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Data Saat Ini</h2>
            <dl class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-400">Nama Laptop</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $currentPayload['name'] ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-400">Brand</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $currentPayload['brand'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-400">Model</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $currentPayload['model'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-400">Serial Number</dt>
                    <dd class="mt-1 font-mono text-slate-700">{{ $currentPayload['serial_number'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-400">Catatan</dt>
                    <dd class="mt-1 text-slate-700">{{ $currentPayload['notes'] ?? '—' }}</dd>
                </div>
            </dl>
            @if(!empty($currentPayload['specifications']))
                @php($specs = $currentPayload['specifications'])
                <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">CPU</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $specs['cpu'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">RAM</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $specs['ram'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Storage</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $specs['storage'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">OS</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $specs['os'] ?? '—' }}</dd>
                    </div>
                </div>
            @endif
        </div>

        <form action="{{ route('student.laptops.requests.store', $laptop) }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="name">Nama Laptop</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $formValues['name']) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" value="{{ old('brand', $formValues['brand']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('brand')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="model">Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model', $formValues['model']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('model')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="serial_number">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $formValues['serial_number']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('serial_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-600" for="notes">Catatan</label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('notes', $formValues['notes']) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-700">Spesifikasi</h3>
                <p class="text-xs text-slate-400">Isi hanya bagian yang ingin diperbarui.</p>
                <div class="mt-3 grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500" for="spec_cpu">CPU</label>
                        <input type="text" id="spec_cpu" name="spec_cpu" value="{{ old('spec_cpu', $formValues['spec_cpu']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('spec_cpu')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500" for="spec_ram">RAM</label>
                        <input type="text" id="spec_ram" name="spec_ram" value="{{ old('spec_ram', $formValues['spec_ram']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('spec_ram')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500" for="spec_storage">Storage</label>
                        <input type="text" id="spec_storage" name="spec_storage" value="{{ old('spec_storage', $formValues['spec_storage']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('spec_storage')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500" for="spec_os">OS</label>
                        <input type="text" id="spec_os" name="spec_os" value="{{ old('spec_os', $formValues['spec_os']) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('spec_os')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('student.laptops.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Batal</a>
                <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Kirim Permintaan
                </button>
            </div>
        </form>
    </div>
@endsection
