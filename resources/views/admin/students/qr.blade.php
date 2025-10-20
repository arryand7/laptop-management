@extends('layouts.app')

@section('title', 'QR Siswa')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.students.show', $student) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cetak</button>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm print:border print:shadow-none">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kartu Peminjaman Laptop</p>
            <h1 class="mt-2 text-xl font-semibold text-slate-800">{{ $student->name }}</h1>
            <p class="text-sm text-slate-500">{{ $student->student_number }} · {{ $student->classroom }}</p>
            <p class="mt-1 text-xs font-mono text-slate-500">Kode Kartu: {{ $student->card_code }}</p>

            <div class="mt-6 flex justify-center">
                {!! $qrSvg !!}
            </div>

            <p class="mt-4 text-xs text-slate-500">QR ini berisi kode kartu siswa dan digunakan untuk proses peminjaman & pengembalian laptop. Pastikan kartu dilaminasi.</p>
        </div>
    </div>
@endsection
