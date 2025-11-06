@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi - Email SMTP')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Pengaturan Email SMTP</h1>
                <p class="mt-1 text-sm text-slate-500">Konfigurasi kredensial email yang digunakan untuk reset password atau notifikasi pelanggaran.</p>
            </div>
            <div class="flex gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('admin.settings.lending') }}" class="text-slate-500 hover:text-slate-700">← Peraturan Laptop</a>
                <a href="{{ route('admin.settings.safe-exam-browser') }}" class="text-blue-600 hover:text-blue-500">Safe Exam Browser →</a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.mail.update') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="smtp_host" class="block text-sm font-semibold text-slate-600">Host SMTP</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $setting->smtp_host) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('smtp_host') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-semibold text-slate-600">Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $setting->smtp_port) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('smtp_port') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="smtp_encryption" class="block text-sm font-semibold text-slate-600">Enkripsi</label>
                    <select id="smtp_encryption" name="smtp_encryption" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @php $encryption = old('smtp_encryption', $setting->smtp_encryption ?? 'none'); @endphp
                        <option value="none" @selected($encryption === 'none' || !$encryption)>Tidak ada</option>
                        <option value="ssl" @selected($encryption === 'ssl')>SSL</option>
                        <option value="tls" @selected($encryption === 'tls')>TLS</option>
                    </select>
                    @error('smtp_encryption') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="smtp_username" class="block text-sm font-semibold text-slate-600">Username</label>
                    <input type="text" id="smtp_username" name="smtp_username" value="{{ old('smtp_username', $setting->smtp_username) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('smtp_username') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="smtp_password" class="block text-sm font-semibold text-slate-600">Password</label>
                <input type="password" id="smtp_password" name="smtp_password" placeholder="••••••" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <p class="mt-1 text-xs text-slate-400">Biarkan kosong jika tidak ingin mengubah password.</p>
                @error('smtp_password') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Catatan</p>
                <ul class="mt-1 list-disc pl-5">
                    <li>Pastikan kredensial sesuai dengan penyedia email (mis. Gmail, Outlook, SendGrid).</li>
                    <li>Port umum: 465 (SSL), 587 (TLS).</li>
                    <li>Perubahan ini tidak akan memodifikasi file <code>.env</code>, namun aplikasi akan menggunakan konfigurasi ini saat mengirim email.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
