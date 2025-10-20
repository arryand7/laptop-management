@extends('layouts.app')

@section('title', 'Tambah Pelanggaran')

@section('content')
    <div class="max-w-4xl space-y-6">
        <a href="{{ route('admin.violations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali ke daftar pelanggaran</a>

        <div>
            <h1 class="text-xl font-semibold text-slate-800">Catat Pelanggaran Baru</h1>
            <p class="text-sm text-slate-500">Gunakan formulir ini untuk menambahkan pelanggaran secara manual, misalnya ketika siswa melakukan pelanggaran di luar sistem peminjaman.</p>
        </div>

        <form action="{{ route('admin.violations.store') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="student_id" class="block text-sm font-semibold text-slate-600">Siswa</label>
                <select id="student_id" name="student_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Pilih siswa</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                            {{ $student->student_number }} · {{ $student->name }} ({{ $student->classroom }})
                        </option>
                    @endforeach
                </select>
                @error('student_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="transaction_code" class="block text-sm font-semibold text-slate-600">Kode Transaksi (opsional)</label>
                    <input type="text" id="transaction_code" name="transaction_code" value="{{ old('transaction_code') }}" placeholder="Contoh: TRX-2024-001" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-400">Isi bila pelanggaran terkait transaksi peminjaman tertentu.</p>
                    @error('transaction_code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="occurred_at" class="block text-sm font-semibold text-slate-600">Waktu Kejadian</label>
                    <input type="datetime-local" id="occurred_at" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('occurred_at')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="points" class="block text-sm font-semibold text-slate-600">Poin Pelanggaran</label>
                    <input type="number" id="points" name="points" value="{{ old('points', 1) }}" min="1" max="10" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-400">Setiap poin akan menambah hitungan pelanggaran siswa.</p>
                    @error('points')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold text-slate-600">Catatan Pelanggaran</label>
                <textarea id="notes" name="notes" rows="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Jelaskan detail pelanggaran yang terjadi">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.violations.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Batal</a>
                <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Simpan Pelanggaran
                </button>
            </div>
        </form>
    </div>
@endsection
