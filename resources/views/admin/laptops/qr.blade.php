@extends('layouts.app')

@section('title', 'QR Laptop')

@section('content')
    <div class="max-w-xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3 no-print">
            <a href="{{ route('admin.laptops.show', $laptop) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Kembali</a>
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cetak</button>
        </div>

        <div class="flex justify-center">
            <div class="qr-label">
                <p class="qr-code"><strong>{{ $laptop->code }}</strong></p>
                <div class="qr-meta">
                    @if($laptop->owner)
                        <p class="qr-owner">{{ \Illuminate\Support\Str::limit($laptop->owner->name, 40) }}</p>
                    @endif
                </div>
                <div class="qr-box">
                    {!! $qrSvg !!}
                </div>
                <div class="qr-meta">
                    <p class="qr-name"><strong>{{ \Illuminate\Support\Str::limit($laptop->owner->student_number, 10) }}</strong></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .qr-label {
            width: 5cm;
            height: 3.5cm;
            border: 3px solid #cbd5f5;
            padding: 0.1cm;
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
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #1e293b;
            margin: 0;
        }
        .qr-name {
            font-size: 0.7rem;
            margin: 0;
            color: #475569;
            font-weight: 600;
        }
        .qr-owner {
            font-size: 0.75rem;
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
            width: 1.8cm;
            height: 1.8cm;
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
