@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi - Identitas')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Identitas Aplikasi</h1>
                <p class="mt-1 text-sm text-slate-500">Perbarui nama aplikasi, deskripsi, kontak, dan logo yang tampil kepada pengguna.</p>
            </div>
            <a href="{{ route('admin.settings.lending') }}" class="text-xs font-semibold uppercase text-blue-600 hover:text-blue-500">Atur Peraturan Laptop →</a>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.application.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="site_name" class="block text-sm font-semibold text-slate-600">Nama Aplikasi</label>
                    <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $setting->site_name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('site_name') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="site_description" class="block text-sm font-semibold text-slate-600">Deskripsi</label>
                    <textarea id="site_description" name="site_description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('site_description', $setting->site_description) }}</textarea>
                    @error('site_description') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="contact_email" class="block text-sm font-semibold text-slate-600">Email Kontak</label>
                        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $setting->contact_email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('contact_email') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contact_phone" class="block text-sm font-semibold text-slate-600">No. Telepon</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('contact_phone') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="contact_address" class="block text-sm font-semibold text-slate-600">Alamat</label>
                    <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $setting->contact_address) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('contact_address') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-semibold text-slate-600">Zona Waktu</label>
                    <select id="timezone" name="timezone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Ikuti default server ({{ config('app.timezone') }})</option>
                        @foreach($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $setting->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Contoh: Asia/Jakarta.</p>
                    @error('timezone') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="logo" class="block text-sm font-semibold text-slate-600">Logo Aplikasi</label>
                    <input type="file" id="logo" name="logo" accept="image/*" class="mt-1 w-full text-sm text-slate-600">
                    <p class="mt-1 text-xs text-slate-400">Disarankan format PNG/SVG persegi, ukuran maksimal 2MB.</p>
                    @error('logo') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                @if($setting->logo_path)
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Pratinjau</p>
                        <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="Logo" class="mt-2 h-24 w-24 rounded-lg border border-slate-200 object-cover">
                    </div>
                @endif
            </div>

            <div class="space-y-4 border-t border-slate-200 pt-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">SSO Sabira Connect</h2>
                    <p class="mt-1 text-sm text-slate-500">Atur koneksi OAuth2 ke portal SSO. Isi Client ID/Secret sesuai aplikasi yang terdaftar di Gate.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="sso_base_url" class="block text-sm font-semibold text-slate-600">Base URL SSO</label>
                        <input type="url" id="sso_base_url" name="sso_base_url" value="{{ old('sso_base_url', $setting->sso_base_url) }}" placeholder="https://gate.sabira-iibs.id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('sso_base_url') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sso_redirect_uri" class="block text-sm font-semibold text-slate-600">Redirect URI</label>
                        <input type="url" id="sso_redirect_uri" name="sso_redirect_uri" value="{{ old('sso_redirect_uri', $setting->sso_redirect_uri) }}" placeholder="{{ route('sso.callback') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-slate-400">Gunakan endpoint callback aplikasi ini (contoh: {{ route('sso.callback') }}).</p>
                        @error('sso_redirect_uri') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="sso_client_id" class="block text-sm font-semibold text-slate-600">Client ID</label>
                        <input type="text" id="sso_client_id" name="sso_client_id" value="{{ old('sso_client_id', $setting->sso_client_id) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('sso_client_id') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sso_client_secret" class="block text-sm font-semibold text-slate-600">Client Secret</label>
                        <input type="password" id="sso_client_secret" name="sso_client_secret" placeholder="********" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-slate-400">Kosongkan jika tidak ingin mengubah secret.</p>
                        @error('sso_client_secret') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="sso_scopes" class="block text-sm font-semibold text-slate-600">Scopes</label>
                    <input type="text" id="sso_scopes" name="sso_scopes" value="{{ old('sso_scopes', $setting->sso_scopes) }}" placeholder="openid profile email roles" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('sso_scopes') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="reset" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
