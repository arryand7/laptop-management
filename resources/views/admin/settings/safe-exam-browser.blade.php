@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi - Safe Exam Browser')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Safe Exam Browser</h1>
                <p class="mt-1 text-sm text-slate-500">Konfigurasi SEB untuk mengamankan ujian berbasis laptop. Atur tautan konfigurasi, Browser Exam Key, dan file template klien.</p>
            </div>
            <div class="flex gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('admin.settings.mail') }}" class="text-slate-500 hover:text-slate-700">← Pengaturan Email</a>
                <a href="{{ route('admin.settings.ai') }}" class="text-blue-600 hover:text-blue-500">Integrasi AI →</a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.safe-exam-browser.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="flex items-start justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700" for="seb_enabled">Aktifkan Safe Exam Browser</label>
                    <p class="mt-1 text-xs text-slate-500">Saat aktif, aplikasi akan menggunakan detail di bawah ini untuk memvalidasi akses ujian melalui SEB.</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" id="seb_enabled" name="seb_enabled" value="1" class="peer sr-only" @checked(old('seb_enabled', $setting->seb_enabled))>
                    <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-blue-600"></span>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                </label>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="seb_config_link" class="block text-sm font-semibold text-slate-600">Tautan Konfigurasi SEB (opsional)</label>
                    <input type="url" id="seb_config_link" name="seb_config_link" value="{{ old('seb_config_link', $setting->seb_config_link) }}" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-500">Bagikan URL konfigurasi SEB kepada peserta ujian. Kosongkan jika tidak digunakan.</p>
                    @error('seb_config_link') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="seb_browser_exam_key" class="block text-sm font-semibold text-slate-600">Browser Exam Key (BEK)</label>
                        <input type="text" id="seb_browser_exam_key" name="seb_browser_exam_key" value="{{ old('seb_browser_exam_key', $setting->seb_browser_exam_key) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-slate-500">Salin BEK dari konfigurasi SEB untuk memastikan hanya klien resmi yang dapat mengakses ujian.</p>
                        @error('seb_browser_exam_key') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="seb_exit_key_combination" class="block text-sm font-semibold text-slate-600">Kombinasi Tombol Keluar</label>
                        <input type="text" id="seb_exit_key_combination" name="seb_exit_key_combination" value="{{ old('seb_exit_key_combination', $setting->seb_exit_key_combination) }}" placeholder="Contoh: CTRL+ALT+SHIFT+Q" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-slate-500">Gunakan format standar untuk memandu pengawas saat menutup SEB di akhir ujian.</p>
                        @error('seb_exit_key_combination') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="seb_config_password" class="block text-sm font-semibold text-slate-600">Password Konfigurasi</label>
                    <input type="password" id="seb_config_password" name="seb_config_password" placeholder="••••••" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak berubah. Centang opsi di bawah untuk menghapus password.</p>
                    @error('seb_config_password') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-rose-500">
                        <input type="checkbox" name="seb_clear_password" value="1" class="h-3.5 w-3.5 text-rose-500 focus:ring-rose-400" @checked(old('seb_clear_password'))>
                        Hapus password konfigurasi yang tersimpan
                    </label>
                    @error('seb_clear_password') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-semibold text-slate-600" for="seb_config_file">File Konfigurasi Client (.seb)</label>
                <input type="file" id="seb_config_file" name="seb_config_file" accept=".seb,.json,.xml,.cfg" class="w-full text-sm text-slate-600">
                <p class="mt-1 text-xs text-slate-500">Unggah file konfigurasi SEB yang dibagikan kepada peserta. Maksimal 4MB.</p>
                @error('seb_config_file') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror

                @if($setting->seb_client_config_path)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        <p class="font-semibold text-slate-700">File tersimpan</p>
                        <div class="mt-1 flex items-center justify-between">
                            <a href="{{ asset('storage/' . $setting->seb_client_config_path) }}" target="_blank" class="text-blue-600 hover:text-blue-500">Unduh konfigurasi saat ini</a>
                            <label class="inline-flex items-center gap-2 text-rose-500">
                                <input type="checkbox" name="seb_remove_config" value="1" class="h-3.5 w-3.5 text-rose-500 focus:ring-rose-400" @checked(old('seb_remove_config'))>
                                Hapus file konfigurasi
                            </label>
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <label for="seb_additional_notes" class="block text-sm font-semibold text-slate-600">Catatan Tambahan</label>
                <textarea id="seb_additional_notes" name="seb_additional_notes" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('seb_additional_notes', $setting->seb_additional_notes) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Catatan internal untuk tim pengawas atau dokumentasi pelaksanaan ujian.</p>
                @error('seb_additional_notes') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-700">Tips Integrasi SEB</p>
                <ul class="mt-1 list-disc pl-5">
                    <li>Pastikan file konfigurasi yang diunggah sudah dienkripsi password jika dibutuhkan.</li>
                    <li>Bagikan Browser Exam Key dan password konfigurasi hanya kepada pengawas resmi.</li>
                    <li>Uji coba konfigurasi pada beberapa perangkat sebelum ujian berlangsung.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
@endsection
