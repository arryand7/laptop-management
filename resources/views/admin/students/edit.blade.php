@extends('layouts.app')

@section('title', 'Ubah Data Siswa')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('admin.students.show', $student) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
        <h1 class="mt-2 text-xl font-semibold text-slate-800">Ubah Data Siswa</h1>

        <form action="{{ route('admin.students.update', $student) }}" method="POST" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $student->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $student->email) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="student_number">NIS</label>
                    <input type="text" id="student_number" name="student_number" value="{{ old('student_number', $student->student_number) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="card_code">Kode Kartu</label>
                    <input type="text" id="card_code" name="card_code" value="{{ old('card_code', $student->card_code) }}" placeholder="Scan/masukkan kode kartu" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="male" @selected(old('gender', $student->gender) === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender', $student->gender) === 'female')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="classroom">Kelas</label>
                    <input type="text" id="classroom" name="classroom" value="{{ old('classroom', $student->classroom) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="phone">No. HP</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $student->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="password">Reset Kata Sandi</label>
                    <input type="text" id="password" name="password" placeholder="Biarkan kosong jika tidak diubah" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $student->is_active)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <label for="is_active" class="text-sm text-slate-600">Akun aktif</label>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Simpan Perubahan</button>
        </form>
    </div>
@endsection
