@extends('layouts.app')

@section('title', 'Cetak QR Laptop')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between no-print">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Cetak QR Laptop</h1>
                <p class="mt-1 text-sm text-slate-500">Dipilih {{ $entries->count() }} laptop · Generate {{ $generatedAt->translatedFormat('d M Y H:i') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase">
                <a href="{{ route('admin.laptops.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-arrow-left text-blue-500"></i> Kembali
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">
                    <i class="fas fa-print text-blue-500"></i> Cetak Semua
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm print:border-0 print:shadow-none">
            <div class="qr-grid">
                @foreach($entries as $entry)
                    <div class="qr-label">
                        <div class="qr-meta">
                            <p class="qr-code"><strong>{{ $entry['laptop']->code }}</strong></p>
                            @if($entry['laptop']->owner)
                                <p class="qr-owner">{{ \Illuminate\Support\Str::limit($entry['laptop']->owner->name, 19) }}</p>
                            @endif
                        </div>
                        <div class="qr-box">
                            {!! $entry['qrSvg'] !!}
                        </div>
                        <div class="qr-meta">
                        @if($entry['laptop']->owner)
                                <p class="qr-name"><strong>{{ \Illuminate\Support\Str::limit($entry['laptop']->owner->student_number, 10) }}</strong></p>
                        @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(3.07cm, 1fr));
            gap: 1rem;
        }
        .qr-label {
            width: 3.5cm;
            height: 4.7cm;
            border: 1px solid #cbd5f5;
            border-radius: 0.6rem;
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
            .qr-grid {
                gap: 0.5rem;
            }
            body {
                background: #ffffff;
            }
        }
    </style>
@endpush
