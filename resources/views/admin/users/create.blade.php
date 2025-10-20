@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
        <h1 class="mt-2 text-xl font-semibold text-slate-800">Tambah User Admin/Staff</h1>
        <p class="text-sm text-slate-500">Kata sandi default adalah <code>password</code> bila tidak diisi.</p>

        <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-600">Nama</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-600">Role</label>
                    <select id="role" name="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                    </select>
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-600">Telepon</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600">Kata Sandi</label>
                    <input type="text" id="password" name="password" placeholder="opsional"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="is_active" class="text-sm text-slate-600">Akun aktif</label>
                </div>
                <div>
                    <label for="avatar" class="block text-sm font-medium text-slate-600">Foto Profil</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-400">Maks 1MB, format JPG/PNG.</p>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-slate-700">Hak Akses Modul</h2>
                <p class="text-xs text-slate-500">Centang menu/fitur yang dapat diakses. Kosongkan untuk menggunakan akses bawaan per role.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach($modules as $module)
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <input type="checkbox" name="modules[]" value="{{ $module->key }}" @checked(in_array($module->key, (array) old('modules', [])))
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="text-sm font-semibold text-slate-700">{{ $module->name }}</span>
                                @if($module->description)
                                    <span class="block text-xs text-slate-500">{{ $module->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('modules')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Simpan Data
            </button>
        </form>
    </div>
@endsection
