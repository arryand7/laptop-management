@extends('layouts.app')

@section('title', 'Detail Permintaan Perubahan Laptop')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('admin.laptop-requests.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar permintaan
        </a>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @php
                $statusMeta = [
                    \App\Models\LaptopUpdateRequest::STATUS_PENDING => ['Menunggu konfirmasi', 'bg-amber-100 text-amber-700'],
                    \App\Models\LaptopUpdateRequest::STATUS_APPROVED => ['Disetujui', 'bg-emerald-100 text-emerald-700'],
                    \App\Models\LaptopUpdateRequest::STATUS_REJECTED => ['Ditolak', 'bg-rose-100 text-rose-700'],
                ][$request->status];
            @endphp
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Permintaan Perubahan Laptop</h1>
                    <p class="text-sm text-slate-500">
                        Diajukan oleh <span class="font-medium text-slate-700">{{ $request->student->name }}</span>
                        pada {{ $request->created_at?->translatedFormat('d M Y H:i') }}.
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $statusMeta[1] }}">
                    {{ $statusMeta[0] }}
                </span>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Siswa</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $request->student->name }}</p>
                    <p class="text-xs text-slate-500">{{ $request->student->student_number }} · {{ $request->student->classroom }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Laptop</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $request->laptop->name }}</p>
                    <p class="text-xs text-slate-500">{{ $request->laptop->code }}</p>
                </div>
            </div>

            @if(!$request->isPending())
                <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Diproses oleh</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $request->admin?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500">Pada {{ $request->processed_at?->translatedFormat('d M Y H:i') ?? '-' }}</p>
                    @if($request->admin_notes)
                        <p class="mt-2 text-sm text-slate-600">Catatan: {{ $request->admin_notes }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Perubahan Data Utama</h2>
                <div class="mt-4 space-y-4">
                    @foreach($fieldDiffs as $diff)
                        <div class="rounded-xl border border-slate-100 px-4 py-3 {{ $diff['changed'] ? 'bg-emerald-50/40' : 'bg-slate-50' }}">
                            <p class="text-xs uppercase tracking-wide text-slate-400">{{ $diff['label'] }}</p>
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Data Saat Diajukan</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $diff['original'] }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Usulan Siswa</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $diff['proposed'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Perubahan Spesifikasi</h2>
                @if(empty($specDiffs))
                    <p class="mt-3 text-sm text-slate-500">Tidak ada data spesifikasi yang diajukan.</p>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach($specDiffs as $diff)
                            <div class="rounded-xl border border-slate-100 px-4 py-3 {{ $diff['changed'] ? 'bg-emerald-50/40' : 'bg-slate-50' }}">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ $diff['label'] }}</p>
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Data Saat Diajukan</p>
                                        <p class="mt-1 text-sm font-medium text-slate-700">{{ $diff['original'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Usulan Siswa</p>
                                        <p class="mt-1 text-sm font-medium text-slate-700">{{ $diff['proposed'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($request->isPending())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Konfirmasi Admin</h2>
                <p class="text-xs text-slate-500">Anda dapat menambahkan catatan untuk siswa (opsional) sebelum menyetujui atau menolak.</p>

                @if($errors->has('admin_notes'))
                    <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-600">
                        {{ $errors->first('admin_notes') }}
                    </p>
                @endif

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <form id="approve-form" action="{{ route('admin.laptop-requests.approve', $request) }}" method="POST" class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                        @csrf
                        @method('PATCH')
                        <label for="admin_notes_approve" class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Catatan (opsional)</label>
                        <textarea id="admin_notes_approve" name="admin_notes" rows="3" class="w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('admin_notes') }}</textarea>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            <i class="fas fa-check mr-2"></i> Setujui dan Terapkan
                        </button>
                    </form>

                    <form id="reject-form" action="{{ route('admin.laptop-requests.reject', $request) }}" method="POST" class="space-y-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 shadow-sm">
                        @csrf
                        @method('PATCH')
                        <label for="admin_notes_reject" class="text-xs font-semibold uppercase tracking-wide text-rose-700">Alasan penolakan (opsional)</label>
                        <textarea id="admin_notes_reject" name="admin_notes" rows="3" class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20">{{ old('admin_notes') }}</textarea>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                            <i class="fas fa-times mr-2"></i> Tolak Permintaan
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
