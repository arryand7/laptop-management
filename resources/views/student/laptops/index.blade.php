@extends('layouts.app')

@section('title', 'Laptop Saya')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Laptop Milik Saya</h1>
            <p class="text-sm text-slate-500">Ajukan perubahan data perangkat Anda jika terdapat informasi yang perlu diperbarui.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('student.laptops.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-black shadow hover:bg-blue-500">
                    <i class="fas fa-plus-circle"></i> Tambah Laptop Baru
                </a>
            </div>
        </div>

        @if($laptops->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm">
                <h2 class="text-base font-semibold text-slate-700">Belum ada laptop terdaftar</h2>
                <p class="mt-2 text-sm text-slate-500">Hubungi admin apabila Anda memiliki laptop pribadi yang belum tercatat pada sistem.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($laptops as $laptop)
                    @php
                        $latestRequest = $laptop->updateRequests->first();
                        $statusLabel = [
                            \App\Models\LaptopUpdateRequest::STATUS_PENDING => ['Menunggu konfirmasi', 'bg-amber-100 text-amber-700'],
                            \App\Models\LaptopUpdateRequest::STATUS_APPROVED => ['Disetujui', 'bg-emerald-100 text-emerald-700'],
                            \App\Models\LaptopUpdateRequest::STATUS_REJECTED => ['Ditolak', 'bg-rose-100 text-rose-700'],
                        ][$latestRequest->status ?? ''] ?? null;
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">{{ $laptop->name }}</h2>
                                <p class="text-sm text-slate-500">{{ $laptop->code }}</p>
                            </div>
                            @if($statusLabel)
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusLabel[1] }}">
                                    {{ $statusLabel[0] }}
                                </span>
                            @endif
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-600">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Brand</dt>
                                <dd class="mt-1 font-medium text-slate-700">{{ $laptop->brand ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Model</dt>
                                <dd class="mt-1 font-medium text-slate-700">{{ $laptop->model ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Serial Number</dt>
                                <dd class="mt-1 font-mono text-slate-700">{{ $laptop->serial_number ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Catatan</dt>
                                <dd class="mt-1 text-slate-700">{{ $laptop->notes ?? '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-slate-400">
                                Terakhir diperbarui {{ $laptop->updated_at?->diffForHumans() ?? 'tidak diketahui' }}
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('student.laptops.qr', $laptop) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                                    Cetak QR
                                </a>
                                <a href="{{ route('student.laptops.edit', $laptop) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                                    Ajukan perubahan
                                </a>
                            </div>
                        </div>

                        @if($latestRequest && $latestRequest->isRejected())
                            <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-600">
                                Permintaan sebelumnya ditolak oleh admin{{ $latestRequest->admin_notes ? ' • ' . $latestRequest->admin_notes : '' }}.
                            </p>
                        @elseif($latestRequest && $latestRequest->isPending())
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                Menunggu persetujuan admin sejak {{ $latestRequest->created_at?->diffForHumans() }}.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
