@extends('layouts.app')

@section('title', 'QR Laptop')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.laptops.show', $laptop) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cetak</button>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm print:border print:shadow-none">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Label Inventaris Laptop</p>
            <h1 class="mt-2 text-xl font-semibold text-slate-800">{{ $laptop->name }}</h1>
            <p class="text-sm text-slate-500">Kode: {{ $laptop->code }}</p>

            <div class="mt-6 flex justify-center">
                {!! $qrSvg !!}
            </div>

            <p class="mt-4 text-xs text-slate-500">Tempelkan label pada laptop agar proses scan lebih cepat.</p>
        </div>
    </div>
@endsection
