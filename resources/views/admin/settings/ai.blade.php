@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi - Integrasi AI')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Integrasi AI</h1>
                <p class="mt-1 text-sm text-slate-500">Simpan API key untuk penyedia AI yang digunakan chatbot dan fitur analitik.</p>
            </div>
            <a href="{{ route('admin.settings.mail') }}" class="text-xs font-semibold uppercase text-slate-500 hover:text-slate-700">← Pengaturan Email</a>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.ai.update') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label for="ai_default_provider" class="block text-sm font-semibold text-slate-600">Penyedia AI Default</label>
                <select id="ai_default_provider" name="ai_default_provider" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @php $provider = old('ai_default_provider', $setting->ai_default_provider ?? 'openai'); @endphp
                    <option value="openai" @selected($provider === 'openai')>OpenAI</option>
                    <option value="gemini" @selected($provider === 'gemini')>Google Gemini</option>
                    <option value="huggingface" @selected($provider === 'huggingface')>HuggingFace</option>
                </select>
                @error('ai_default_provider') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-3">
                <div>
                    <label for="openai_model" class="block text-sm font-semibold text-slate-600">Model OpenAI</label>
                    <input type="text" id="openai_model" name="openai_model" value="{{ old('openai_model', $setting->openai_model ?? config('services.openai.model')) }}" placeholder="contoh: gpt-4o-mini" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('openai_model') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="openai_api_key" class="block text-sm font-semibold text-slate-600">OpenAI API Key</label>
                    <input type="password" id="openai_api_key" name="openai_api_key" placeholder="••••••" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-400">Biarkan kosong jika tidak ingin mengubah.</p>
                    @error('openai_api_key') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="space-y-3">
                <div>
                    <label for="gemini_model" class="block text-sm font-semibold text-slate-600">Model Gemini</label>
                    <input type="text" id="gemini_model" name="gemini_model" value="{{ old('gemini_model', $setting->gemini_model ?? config('services.gemini.model')) }}" placeholder="contoh: gemini-1.5-flash" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('gemini_model') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="gemini_api_key" class="block text-sm font-semibold text-slate-600">Google Gemini API Key</label>
                    <input type="password" id="gemini_api_key" name="gemini_api_key" placeholder="••••••" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-slate-400">Biarkan kosong jika tidak ingin mengubah.</p>
                    @error('gemini_api_key') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="huggingface_model" class="block text-sm font-semibold text-slate-600">Model HuggingFace</label>
                    <input type="text" id="huggingface_model" name="huggingface_model" value="{{ old('huggingface_model', $setting->huggingface_model ?? config('services.huggingface.model')) }}" placeholder="contoh: mistralai/Mistral-7B-Instruct-v0.2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('huggingface_model') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="huggingface_api_key" class="block text-sm font-semibold text-slate-600">HuggingFace API Key</label>
                    <input type="password" id="huggingface_api_key" name="huggingface_api_key" placeholder="••••••" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('huggingface_api_key') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Catatan</p>
                <ul class="mt-1 list-disc pl-5">
                    <li>API key disimpan aman di database aplikasi. Pastikan hanya administrator terpercaya yang memiliki akses.</li>
                    <li>Pilih penyedia default sesuai dengan key yang aktif. Chatbot akan otomatis menggunakan provider tersebut.</li>
                    <li>Anda dapat mengosongkan model jika ingin menggunakan default dari konfigurasi sistem.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
