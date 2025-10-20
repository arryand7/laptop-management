@extends('layouts.app')

@section('title', 'Ubah Data Laptop')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('admin.laptops.show', $laptop) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
        <h1 class="mt-2 text-xl font-semibold text-slate-800">Ubah Data Laptop</h1>

        <form action="{{ route('admin.laptops.update', $laptop) }}" method="POST" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="name">Nama Laptop</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $laptop->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" value="{{ old('brand', $laptop->brand) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="model">Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model', $laptop->model) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="serial_number">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $laptop->serial_number) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="status">Status</label>
                    <select id="status" name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @foreach(['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'maintenance' => 'Maintenance', 'retired' => 'Nonaktif'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $laptop->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="owner_id">Pemilik (Siswa)</label>
                    <select id="owner_id" name="owner_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Tidak ada</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected((string) old('owner_id', $laptop->owner_id) === (string) $student->id)>
                                {{ $student->student_number }} · {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="notes">Catatan</label>
                    <input type="text" id="notes" name="notes" value="{{ old('notes', $laptop->notes) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="spec_cpu">CPU</label>
                    <input type="text" id="spec_cpu" name="spec_cpu" value="{{ old('spec_cpu', data_get($laptop->specifications, 'cpu')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="spec_ram">RAM</label>
                    <input type="text" id="spec_ram" name="spec_ram" value="{{ old('spec_ram', data_get($laptop->specifications, 'ram')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="spec_storage">Storage</label>
                    <input type="text" id="spec_storage" name="spec_storage" value="{{ old('spec_storage', data_get($laptop->specifications, 'storage')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="spec_os">Sistem Operasi</label>
                    <input type="text" id="spec_os" name="spec_os" value="{{ old('spec_os', data_get($laptop->specifications, 'os')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="regenerate_qr" name="regenerate_qr" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <label for="regenerate_qr" class="text-sm text-slate-600">Generate ulang QR Code</label>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Simpan Perubahan</button>
        </form>
    </div>
@endsection
