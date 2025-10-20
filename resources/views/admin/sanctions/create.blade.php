@extends('layouts.app')

@section('title', 'Tambah Sanksi')

@section('content')
    <div class="max-w-4xl space-y-6">
        <a href="{{ route('admin.sanctions.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali ke daftar sanksi</a>

        <div>
            <h1 class="text-xl font-semibold text-slate-800">Buat Sanksi Baru</h1>
            <p class="text-sm text-slate-500">Gunakan formulir ini untuk menonaktifkan akses siswa secara manual ketika ditemukan pelanggaran berat.</p>
        </div>

        <form action="{{ route('admin.sanctions.store') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                    <label for="starts_at" class="block text-sm font-semibold text-slate-600">Mulai Berlaku</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('starts_at')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-semibold text-slate-600">Berakhir</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('ends_at')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-semibold text-slate-600">Alasan Sanksi</label>
                <textarea id="reason" name="reason" rows="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Contoh: Akses ke situs terlarang selama jam pelajaran">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.sanctions.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Batal</a>
                <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Simpan Sanksi
                </button>
            </div>
        </form>
    </div>
@endsection
