@extends('layouts.app')

@section('title', 'Sinkronisasi SSO')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Sinkronisasi SSO</h1>
                <p class="mt-1 text-sm text-slate-500">Import mapping dari Sabira Connect untuk mengisi <code>sso_sub</code> tanpa akses terminal.</p>
            </div>
            <a href="{{ route('admin.settings.application') }}" class="text-xs font-semibold uppercase text-slate-500 hover:text-slate-700">← Pengaturan Aplikasi</a>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Langkah cepat</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600">
                <li>Di Sabira Connect jalankan <code>php artisan sso:export-mapping --path=storage/sso_mapping.csv</code> atau unduh file mapping yang disediakan.</li>
                <li>Upload file CSV mapping di bawah ini.</li>
                <li>Sistem akan mencocokkan: siswa by NIS (<code>student_number</code>), selain itu by email sekolah.</li>
            </ol>
            <form action="{{ route('admin.settings.sso-sync.run') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="mapping" class="block text-sm font-semibold text-slate-700">File mapping CSV</label>
                    <input type="file" id="mapping" name="mapping" accept=".csv,text/csv" required class="mt-2 w-full text-sm text-slate-600">
                    <p class="mt-1 text-xs text-slate-500">Kolom minimal: sub,email,nis,type</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Jalankan sinkronisasi</button>
                    <a href="{{ asset('storage/sso_mapping.csv') }}" class="text-sm text-blue-600 hover:text-blue-500" target="_blank">Unduh mapping terakhir (jika ada)</a>
                </div>
            </form>
        </div>
    </div>
@endsection
