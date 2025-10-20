@extends('layouts.app')

@section('title', 'Permintaan Perubahan Laptop')

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Permintaan Perubahan Laptop</h1>
            <p class="text-sm text-slate-500">Tinjau dan konfirmasi perubahan data laptop yang diajukan siswa.</p>
        </div>
        <div class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-sm">
            Total {{ $requests->total() }} permintaan
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @php
            $tabs = [
                \App\Models\LaptopUpdateRequest::STATUS_PENDING => 'Menunggu',
                \App\Models\LaptopUpdateRequest::STATUS_APPROVED => 'Disetujui',
                \App\Models\LaptopUpdateRequest::STATUS_REJECTED => 'Ditolak',
                'all' => 'Semua',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.laptop-requests.index', ['status' => $key]) }}"
               class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium {{ $status === $key ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($requests->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-semibold text-slate-700">Belum ada permintaan pada kategori ini.</p>
                <p class="mt-2 text-xs text-slate-500">Siswa dapat mengajukan perubahan melalui halaman laptop mereka masing-masing.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Diajukan</th>
                            <th class="px-4 py-3 text-left">Siswa</th>
                            <th class="px-4 py-3 text-left">Laptop</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Admin</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600">
                        @foreach($requests as $request)
                            @php
                                $statusMeta = [
                                    \App\Models\LaptopUpdateRequest::STATUS_PENDING => ['Menunggu', 'bg-amber-100 text-amber-700'],
                                    \App\Models\LaptopUpdateRequest::STATUS_APPROVED => ['Disetujui', 'bg-emerald-100 text-emerald-700'],
                                    \App\Models\LaptopUpdateRequest::STATUS_REJECTED => ['Ditolak', 'bg-rose-100 text-rose-700'],
                                ][$request->status];
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-700">{{ $request->created_at?->translatedFormat('d M Y H:i') }}</span>
                                        <span class="text-xs text-slate-400">{{ $request->created_at?->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-700">{{ $request->student->name }}</span>
                                        <span class="text-xs text-slate-500">{{ $request->student->student_number }} · {{ $request->student->classroom }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-700">{{ $request->laptop->name }}</span>
                                        <span class="text-xs text-slate-500">{{ $request->laptop->code }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta[1] }}">
                                        {{ $statusMeta[0] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    @if($request->admin)
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700">{{ $request->admin->name }}</span>
                                            <span class="text-xs text-slate-400">{{ $request->processed_at?->translatedFormat('d M Y H:i') }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.laptop-requests.show', $request) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endsection
