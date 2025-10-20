@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-xl font-semibold text-slate-800">Profil Saya</h1>
        <p class="text-sm text-slate-500">Perbarui informasi akun Anda. Kata sandi baru harus dikonfirmasi.</p>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-600">Nama</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-600">Telepon</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600">Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" disabled
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak diubah"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-600">Konfirmasi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi kata sandi"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>

            <div>
                <label for="avatar" class="block text-sm font-medium text-slate-600">Foto Profil</label>
                <div class="mt-2 flex items-center gap-4">
                    <img src="{{ $user->avatar_url }}" alt="Avatar" class="h-16 w-16 rounded-full border border-slate-200 object-cover">
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <p class="mt-1 text-xs text-slate-400">Maks 1MB, format JPG/PNG.</p>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Simpan Perubahan
            </button>
        </form>
    </div>
@endsection
