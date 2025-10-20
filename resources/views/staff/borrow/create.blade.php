@extends('layouts.app')

@section('title', 'Form Peminjaman')

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-xl font-semibold text-slate-800">Form Peminjaman Laptop</h1>
        <p class="mt-1 text-sm text-slate-500">Scan kartu siswa dan QR laptop untuk mempercepat input.</p>

        @php
            $defaultDueValue = old('due_at', isset($defaultDueAt) ? $defaultDueAt->format('Y-m-d\TH:i') : null);
        @endphp
        <form action="{{ route('staff.borrow.store') }}" method="POST" class="mt-6 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-600" for="student_qr">Identitas Siswa</label>
                    <input type="text" id="student_qr" name="student_qr" value="{{ old('student_qr') }}" required autofocus class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Scan/ketik NIS, nama, kode kartu, atau QR" data-lookup="students" data-endpoint="{{ route('staff.lookup.students') }}" data-helper="student-helper" data-suggestions="student-suggestions" data-next="laptop_qr">
                    <div id="student-suggestions" class="lookup-suggestions d-none"></div>
                    <p id="student-helper" data-default="Mulai ketik untuk menampilkan daftar siswa." class="mt-1 text-xs text-slate-500">Mulai ketik untuk menampilkan daftar siswa.</p>
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-600" for="laptop_qr">Identitas Laptop</label>
                    <input type="text" id="laptop_qr" name="laptop_qr" value="{{ old('laptop_qr') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Scan/ketik kode/QR laptop" data-lookup="laptops" data-endpoint="{{ route('staff.lookup.laptops') }}" data-helper="laptop-helper" data-suggestions="laptop-suggestions" data-next="usage_purpose">
                    <div id="laptop-suggestions" class="lookup-suggestions d-none"></div>
                    <p id="laptop-helper" data-default="Mulai ketik untuk menampilkan daftar laptop." class="mt-1 text-xs text-slate-500">Mulai ketik untuk menampilkan daftar laptop.</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600" for="usage_purpose">Keperluan Penggunaan</label>
                <input type="text" id="usage_purpose" name="usage_purpose" value="{{ old('usage_purpose') }}" required placeholder="Contoh: Ujian CBT Kelas XII" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="due_at">Batas Pengembalian</label>
                    <input type="datetime-local" id="due_at" name="due_at" value="{{ $defaultDueValue }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('due_at')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">{{ $defaultDueLabel ?? 'Default mengikuti pengaturan batas pengembalian.' }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600" for="staff_notes">Catatan Petugas (opsional)</label>
                    <input type="text" id="staff_notes" name="staff_notes" value="{{ old('staff_notes') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Catat Peminjaman</button>
        </form>
    </div>
@endsection
