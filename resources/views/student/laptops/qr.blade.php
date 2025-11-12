@extends('layouts.app')

@section('title', 'Cetak QR Laptop Saya')

@section('content')
    <div class="max-w-xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3 no-print">
            <a href="{{ route('student.laptops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cetak</button>
        </div>

        <div class="flex justify-center">
            <div class="qr-label">
                <div class="qr-meta">
                    <p class="qr-code">{{ $laptop->code }}</p>
                    <p class="qr-name">{{ \Illuminate\Support\Str::limit($laptop->name, 22) }}</p>
                    @if($laptop->owner)
                        <p class="qr-owner">{{ \Illuminate\Support\Str::limit($laptop->owner->name, 22) }}</p>
                    @endif
                </div>
                <div class="qr-box">
                    {!! $qrSvg !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .qr-label {
            width: 4cm;
            height: 4cm;
            border: 1px solid #cbd5f5;
            border-radius: 0.6rem;
            padding: 0.35cm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            background: #fff;
        }
        .qr-meta {
            font-size: 0.55rem;
            line-height: 1.2;
        }
        .qr-code {
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #1e293b;
            margin: 0;
        }
        .qr-name {
            margin: 0;
            color: #475569;
            font-weight: 600;
        }
        .qr-owner {
            margin: 0;
            color: #94a3b8;
        }
        .qr-box {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-box svg {
            width: 100%;
            height: 100%;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
            }
        }
    </style>
@endpush
