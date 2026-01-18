@extends('layouts.app')

@section('title', 'Masuk · Sistem Peminjaman Laptop')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl bg-white p-8 shadow-lg shadow-blue-500/5">
            <h1 class="text-2xl font-semibold text-slate-900">Masuk</h1>
            <p class="mt-2 text-sm text-slate-500">Gunakan akun yang sesuai dengan peran Anda.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-600">Kata Sandi</label>
                    </div>
                    <input type="password" id="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Ingat saya
                    </label>
                    <span class="text-xs text-slate-400">Hubungi admin jika lupa kata sandi.</span>
                </div>
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Masuk</button>
            </form>

            <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span>atau</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <a href="{{ route('sso.login') }}" class="w-full inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Masuk dengan Sabira Connect
            </a>
        </div>
    </div>
@endsection
