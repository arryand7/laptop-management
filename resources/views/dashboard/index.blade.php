@extends('layouts.app')

@section('title', 'Dashboard')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Dashboard</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-8">
        @if(!$user->isStudent())
        
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-700">Statistik Laptop</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="info-box">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Jumlah Seluruh Laptop</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-800">{{ $laptopOverviewStats['total'] }}</p>
                    </div>
                    <div class="info-box">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Dimiliki Siswa Laki-laki</p>
                        <p class="mt-2 text-2xl font-semibold text-blue-700">{{ $laptopOverviewStats['male'] }}</p>
                    </div>
                    <div class="info-box">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Dimiliki Siswa Perempuan</p>
                        <p class="mt-2 text-2xl font-semibold text-rose-700">{{ $laptopOverviewStats['female'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-4 sm:col-span-3 lg:col-span-1">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Status Laptop</p>
                        <ul class="mt-2 space-y-1 text-xs text-slate-600">
                            @foreach($laptopStatusSummary as $status)
                                <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                    <span class="font-semibold text-slate-700">{{ $status['label'] }}</span>
                                    <span class="inline-flex items-center rounded bg-slate-200 px-2 py-0.5 font-medium text-slate-700">
                                        {{ $status['total'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Laptop per Kelas</h4>
                    @if($laptopClassBreakdown->isNotEmpty())
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($laptopClassBreakdown as $classroom => $count)
                                <div class="info-box">
                                    <p class="text-sm font-semibold text-slate-700">{{ $classroom }}</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ $count }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Belum ada laptop yang terhubung dengan siswa.</p>
                    @endif
                </div>
            </section>
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Laptop Sedang Dipinjam</h3>
                        <span class="text-xs text-slate-400">{{ $borrowedLaptopList->count() }} unit</span>
                    </div>
                    <div class="mt-4 table-responsive">
                        <table class="table table-striped table-bordered table-sm datatable-default w-100">
                            <thead class="text-xs uppercase text-slate-400">
                                <tr>
                                    <th class="pb-2">Kode</th>
                                    <th class="pb-2">Nama</th>
                                    <th class="pb-2">Pemilik Laptop</th>
                                    <th class="pb-2">Dipinjam Oleh</th>
                                    <th class="pb-2">Jatuh Tempo</th>
                                    <th class="pb-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-600">
                                @forelse($borrowedLaptopList as $laptop)
                                    @php
                                        $activeTrx = $laptop->borrowTransactions->first();
                                    @endphp
                                    <tr>
                                        <td class="py-2 font-medium text-slate-700">{{ $laptop->code }}</td>
                                        <td class="py-2 text-slate-600">{{ $laptop->name }}</td>
                                        <td class="py-2 text-slate-600">
                                            @if($laptop->owner)
                                                {{ $laptop->owner->name }} ({{ $laptop->owner->student_number }})
                                            @else
                                                <span class="text-slate-400">Belum ditetapkan</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-slate-600">{{ $activeTrx?->student?->name ?? '-' }}</td>
                                        <td class="py-2 text-slate-600">{{ $activeTrx?->due_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                        <td class="py-2 text-right">
                                            @if($activeTrx)
                                                <button type="button"
                                                    class="btn-quick-return inline-flex items-center gap-1 rounded bg-emerald-500 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-600 transition-all duration-200"
                                                    data-url="{{ route('staff.return.quick', $activeTrx) }}"
                                                    data-owner="{{ $laptop->owner?->name ?? 'Tidak diketahui' }}"
                                                    data-borrower="{{ $activeTrx->student?->name ?? '-' }}">
                                                    <i class="fas fa-undo-alt"></i> Dikembalikan
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-sm text-slate-500">Tidak ada laptop yang sedang dipinjam.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <br>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" id="activity-section">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Aktivitas Peminjaman</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="activity-presets flex flex-wrap gap-1">
                                <button type="button" class="activity-preset-btn active" data-range="today">Hari ini</button>
                                <button type="button" class="activity-preset-btn" data-range="week">Minggu ini</button>
                                <button type="button" class="activity-preset-btn" data-range="month">Bulan ini</button>
                                <button type="button" class="activity-preset-btn" data-range="all">Semua</button>
                            </div>
                        </div>
                    </div>

                    {{-- Custom date range --}}
                    <div class="mt-3 flex flex-wrap items-end gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500">Dari</label>
                            <input type="date" id="activity-date-from" class="activity-date-input rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500">Sampai</label>
                            <input type="date" id="activity-date-to" class="activity-date-input rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300">
                        </div>
                        <button type="button" id="activity-filter-btn" class="inline-flex items-center gap-1 rounded-lg bg-blue-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-600 transition-colors">
                            <i class="fas fa-search text-[10px]"></i> Tampilkan
                        </button>
                        <span id="activity-count" class="ml-auto text-xs text-slate-400"></span>
                    </div>

                    {{-- Desktop table --}}
                    <div class="mt-4 overflow-x-auto activity-desktop-view">
                        <table class="w-full text-left" id="activity-table">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400 w-10">No</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Nama Peminjam</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pemilik Laptop</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Waktu Dipinjam</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Waktu Dikembalikan</th>
                                    <th class="pb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tujuan Pemakaian</th>
                                </tr>
                            </thead>
                            <tbody id="activity-tbody" class="text-slate-600">
                                <tr><td colspan="7" class="py-8 text-center text-sm text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile card view --}}
                    <div class="mt-4 activity-mobile-view" id="activity-mobile-list">
                        <div class="py-8 text-center text-sm text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700">Top 5 Siswa Terbanyak Pelanggaran</h3>
                    <ul class="mt-4 space-y-3">
                        @forelse($topViolators as $student)
                            <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-3">
                                <div>
                                    <p class="text-xs font-medium text-slate-700"><b>{{ $student->name }}</b> ({{ $student->student_number }} - {{ $student->classroom }})</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-600">{{ $student->violations_count }} pelanggaran</span>
                            </li>
                        @empty
                            <li class="rounded-lg bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">Belum ada data pelanggaran.</li>
                        @endforelse
                    </ul>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700">Tren Peminjaman 7 Hari Terakhir</h3>
                    <canvas id="dailyBorrowChart" class="mt-6 h-64"></canvas>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700">Keterlambatan Pengembalian (4 Minggu)</h3>
                    <canvas id="lateReturnChart" class="mt-6 h-64"></canvas>
                </div>
            </section>

            <section>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-dashboard-card title="Peminjaman Hari Ini" :value="$overview['today_borrowings']" icon="calendar" accent="blue" />
                    <x-dashboard-card title="Peminjaman Minggu Ini" :value="$overview['week_borrowings']" icon="chart" accent="violet" />
                    <x-dashboard-card title="Peminjaman Bulan Ini" :value="$overview['month_borrowings']" icon="spark" accent="amber" />
                    <x-dashboard-card title="Peminjaman Aktif" :value="$overview['active_borrowings']" icon="laptop" accent="emerald" />
                    <x-dashboard-card title="Pengembalian Terlambat" :value="$overview['late_returns']" icon="alert" accent="rose" />
                    <x-dashboard-card title="Siswa Disanksi" :value="$overview['sanctioned_students']" icon="shield" accent="slate" />
                </div>
            </section>
        @else
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700">Peminjaman Aktif</h3>
                    <ul class="mt-4 space-y-3">
                        @forelse($studentSummary['active_borrowings'] as $transaction)
                            <li class="rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3">
                                <p class="text-sm font-semibold text-blue-700">{{ $transaction->laptop?->name }} ({{ $transaction->laptop?->code }})</p>
                                <p class="text-xs text-blue-600">Jatuh tempo: {{ $transaction->due_at?->translatedFormat('d M Y H:i') }}</p>
                                <p class="mt-1 text-xs text-blue-500">Keperluan: {{ $transaction->usage_purpose }}</p>
                            </li>
                        @empty
                            <li class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada peminjaman aktif.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700">Status Pelanggaran</h3>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ $user->violations_count }}</p>
                    <p class="text-sm text-slate-500">Total pelanggaran keterlambatan yang tercatat.</p>
                    @if($user->sanction_ends_at)
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            <p class="font-semibold">Sedang dalam masa sanksi</p>
                            <p class="text-xs">Hingga {{ $user->sanction_ends_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <p class="font-semibold">Tidak ada sanksi aktif.</p>
                        </div>
                    @endif

                    <h4 class="mt-6 text-xs font-semibold uppercase tracking-wide text-slate-500">Riwayat Sanksi</h4>
                    <ul class="mt-2 space-y-2">
                        @forelse($studentSummary['sanctions'] as $sanction)
                            <li class="rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-600">
                                <p class="font-semibold text-slate-700">{{ strtoupper($sanction->status) }}</p>
                                <p>{{ $sanction->starts_at->translatedFormat('d M Y') }} - {{ $sanction->ends_at->translatedFormat('d M Y') }}</p>
                                <p class="mt-1 text-slate-500">{{ $sanction->reason }}</p>
                            </li>
                        @empty
                            <li class="rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-500">Belum ada riwayat sanksi.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-700">Riwayat Peminjaman</h3>
                <div class="mt-4 table-responsive">
                    <table class="table table-striped table-bordered table-sm datatable-default w-100">
                        <thead class="text-xs uppercase text-slate-400">
                            <tr>
                                <th class="pb-2">Tanggal</th>
                                <th class="pb-2">Laptop</th>
                                <th class="pb-2">Keperluan</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600">
                            @forelse($studentSummary['history'] as $transaction)
                                <tr>
                                    <td class="py-2 text-slate-600">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="py-2 text-slate-600">{{ $transaction->laptop?->name }}</td>
                                    <td class="py-2 text-slate-600">{{ $transaction->usage_purpose }}</td>
                                    <td class="py-2">
                                        @if($transaction->status === 'borrowed')
                                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-600">Dipinjam</span>
                                        @elseif($transaction->was_late)
                                            <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Terlambat</span>
                                        @else
                                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Dikembalikan</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-slate-600">{{ $transaction->staff?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-sm text-slate-500">Belum ada riwayat peminjaman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection

@php
    $dailyLabels = $dailyBorrowSeries->pluck('label');
    $dailyValues = $dailyBorrowSeries->pluck('value');
    $weeklyLabels = $weeklyLateSeries->pluck('label');
    $weeklyValues = $weeklyLateSeries->pluck('value');
@endphp

@section('scripts')
    @if(!$user->isStudent())
        {{-- Toast notification container --}}
        <div id="toast-container" style="position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:12px;pointer-events:none;"></div>

        <style>
            @keyframes toastSlideIn {
                from { transform: translateX(120%); opacity: 0; }
                to   { transform: translateX(0); opacity: 1; }
            }
            @keyframes toastSlideOut {
                from { transform: translateX(0); opacity: 1; }
                to   { transform: translateX(120%); opacity: 0; }
            }
            @keyframes progressShrink {
                from { width: 100%; }
                to   { width: 0%; }
            }
            @keyframes toastPulse {
                0%, 100% { box-shadow: 0 8px 32px rgba(16,185,129,0.25); }
                50% { box-shadow: 0 8px 48px rgba(16,185,129,0.45); }
            }
            .toast-notification {
                pointer-events: auto;
                min-width: 360px;
                max-width: 440px;
                border-radius: 16px;
                overflow: hidden;
                animation: toastSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards,
                           toastPulse 2s ease-in-out 0.5s 1;
                position: relative;
            }
            .toast-notification.toast-exit {
                animation: toastSlideOut 0.4s ease-in forwards;
            }
            .toast-inner {
                background: linear-gradient(135deg, #065f46 0%, #047857 40%, #059669 100%);
                padding: 20px 24px 24px;
                color: #fff;
                position: relative;
            }
            .toast-inner::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: radial-gradient(circle at top right, rgba(255,255,255,0.12), transparent 60%);
                pointer-events: none;
            }
            .toast-icon {
                width: 44px;
                height: 44px;
                background: rgba(255,255,255,0.18);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                flex-shrink: 0;
                backdrop-filter: blur(8px);
            }
            .toast-header {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .toast-title {
                font-size: 14px;
                font-weight: 700;
                letter-spacing: 0.02em;
            }
            .toast-subtitle {
                font-size: 11px;
                opacity: 0.8;
                margin-top: 2px;
                font-weight: 500;
            }
            .toast-body {
                margin-top: 14px;
                display: flex;
                gap: 10px;
            }
            .toast-detail {
                flex: 1;
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
                padding: 10px 14px;
                backdrop-filter: blur(4px);
            }
            .toast-detail-label {
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                opacity: 0.7;
                font-weight: 600;
            }
            .toast-detail-value {
                font-size: 13px;
                font-weight: 600;
                margin-top: 3px;
                word-break: break-word;
            }
            .toast-progress {
                height: 3px;
                background: rgba(255,255,255,0.15);
            }
            .toast-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #6ee7b7, #a7f3d0);
                animation: progressShrink 4s linear forwards;
                border-radius: 0 0 16px 16px;
            }
            .toast-close {
                position: absolute;
                top: 12px;
                right: 14px;
                background: rgba(255,255,255,0.15);
                border: none;
                color: #fff;
                width: 24px;
                height: 24px;
                border-radius: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                transition: background 0.2s;
                pointer-events: auto;
            }
            .toast-close:hover {
                background: rgba(255,255,255,0.3);
            }
            .toast-error .toast-inner {
                background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 40%, #b91c1c 100%);
            }
            .toast-error .toast-progress-bar {
                background: linear-gradient(90deg, #fca5a5, #fecaca);
            }
            .btn-quick-return.is-loading {
                pointer-events: none;
                opacity: 0.6;
            }
            .btn-quick-return.is-loading i {
                animation: spin 0.8s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .row-fade-out {
                transition: all 0.5s ease;
                opacity: 0;
                transform: translateX(30px);
            }

            /* ── Activity section ── */
            .activity-preset-btn {
                padding: 4px 12px;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 600;
                color: #64748b;
                background: #f1f5f9;
                border: 1px solid transparent;
                cursor: pointer;
                transition: all 0.2s;
            }
            .activity-preset-btn:hover {
                background: #e2e8f0;
                color: #334155;
            }
            .activity-preset-btn.active {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: #fff;
                border-color: #2563eb;
                box-shadow: 0 2px 8px rgba(37,99,235,0.25);
            }
            .activity-date-input {
                transition: border-color 0.2s, box-shadow 0.2s;
            }

            /* Desktop view: show table, hide mobile */
            .activity-desktop-view { display: block; }
            .activity-mobile-view  { display: none; }

            @media (max-width: 768px) {
                .activity-desktop-view { display: none !important; }
                .activity-mobile-view  { display: block !important; }
            }

            /* Mobile card styles */
            .activity-card {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 12px 14px;
                margin-bottom: 8px;
                background: #fafbfc;
                transition: all 0.2s;
            }
            .activity-card:hover {
                border-color: #cbd5e1;
                background: #f8fafc;
            }
            .activity-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                gap: 8px;
            }
            .activity-card-info {
                flex: 1;
                min-width: 0;
            }
            .activity-card-info .name {
                font-size: 13px;
                font-weight: 600;
                color: #334155;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .activity-card-info .meta {
                font-size: 11px;
                color: #94a3b8;
                margin-top: 2px;
            }
            .activity-expand-btn {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #fff;
                color: #64748b;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
                flex-shrink: 0;
            }
            .activity-expand-btn:hover {
                background: #f1f5f9;
                border-color: #cbd5e1;
            }
            .activity-expand-btn.expanded {
                background: #3b82f6;
                border-color: #3b82f6;
                color: #fff;
                transform: rotate(45deg);
            }
            .activity-card-details {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease;
                opacity: 0;
            }
            .activity-card-details.open {
                max-height: 300px;
                opacity: 1;
                padding-top: 10px;
                margin-top: 10px;
                border-top: 1px solid #e2e8f0;
            }
            .activity-card-details .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                font-size: 12px;
            }
            .activity-card-details .detail-label {
                color: #94a3b8;
                font-weight: 500;
            }
            .activity-card-details .detail-value {
                color: #334155;
                font-weight: 600;
                text-align: right;
            }

            #activity-table tbody tr {
                transition: background 0.15s;
            }
            #activity-table tbody tr:hover {
                background: #f8fafc;
            }
            #activity-table td {
                padding: 10px 8px;
                font-size: 13px;
                border-bottom: 1px solid #f1f5f9;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // ── Chart setup ──
                const dailyCtx = document.getElementById('dailyBorrowChart');
                if (dailyCtx) {
                    new Chart(dailyCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: @json($dailyLabels),
                            datasets: [{
                                label: 'Peminjaman',
                                data: @json($dailyValues),
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.12)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 4,
                                pointBackgroundColor: '#1d4ed8',
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }
                        }
                    });
                }

                const lateCtx = document.getElementById('lateReturnChart');
                if (lateCtx) {
                    new Chart(lateCtx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: @json($weeklyLabels),
                            datasets: [{
                                label: 'Pengembalian Terlambat',
                                data: @json($weeklyValues),
                                backgroundColor: '#f97316',
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }
                        }
                    });
                }

                // ── Toast helper ──
                function showToast({ ownerName, borrowerName, isError = false, errorMsg = '' }) {
                    const container = document.getElementById('toast-container');
                    const toast = document.createElement('div');
                    toast.className = 'toast-notification' + (isError ? ' toast-error' : '');
                    toast.innerHTML = `
                        <div class="toast-inner">
                            <button class="toast-close" onclick="this.closest('.toast-notification').classList.add('toast-exit'); setTimeout(() => this.closest('.toast-notification').remove(), 400);">✕</button>
                            <div class="toast-header">
                                <div class="toast-icon">${isError ? '✕' : '✓'}</div>
                                <div>
                                    <div class="toast-title">${isError ? 'Gagal!' : 'Berhasil Dikembalikan!'}</div>
                                    <div class="toast-subtitle">${isError ? errorMsg : 'Laptop telah ditandai dikembalikan'}</div>
                                </div>
                            </div>
                            ${!isError ? `
                            <div class="toast-body">
                                <div class="toast-detail">
                                    <div class="toast-detail-label">Pemilik Laptop</div>
                                    <div class="toast-detail-value">${ownerName}</div>
                                </div>
                                <div class="toast-detail">
                                    <div class="toast-detail-label">Peminjam</div>
                                    <div class="toast-detail-value">${borrowerName}</div>
                                </div>
                            </div>` : ''}
                        </div>
                        <div class="toast-progress"><div class="toast-progress-bar"></div></div>
                    `;
                    container.appendChild(toast);

                    // Auto-dismiss after 4s
                    setTimeout(() => {
                        toast.classList.add('toast-exit');
                        setTimeout(() => toast.remove(), 400);
                    }, 4000);
                }

                // ── Quick-return AJAX handler ──
                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('.btn-quick-return');
                    if (!btn) return;

                    // Prevent double click
                    if (btn.classList.contains('is-loading')) return;
                    btn.classList.add('is-loading');
                    btn.querySelector('i').className = 'fas fa-spinner';

                    const url = btn.dataset.url;
                    const ownerName = btn.dataset.owner;
                    const borrowerName = btn.dataset.borrower;
                    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
                    const row = btn.closest('tr');

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({})
                    })
                    .then(res => res.json().then(data => ({ ok: res.ok, data })))
                    .then(({ ok, data }) => {
                        if (ok && data.success) {
                            showToast({
                                ownerName: data.owner_name || ownerName,
                                borrowerName: data.borrower_name || borrowerName,
                            });

                            // Fade out the row
                            row.classList.add('row-fade-out');
                            setTimeout(() => {
                                row.remove();

                                // Update the counter in the section header
                                const tbody = document.querySelector('.btn-quick-return')?.closest('tbody');
                                const countEl = document.querySelector('.btn-quick-return')?.closest('.rounded-2xl')?.querySelector('.text-xs.text-slate-400');
                                if (countEl) {
                                    const remaining = tbody ? tbody.querySelectorAll('tr').length : 0;
                                    countEl.textContent = remaining + ' unit';
                                }

                                // If no rows left, show empty state
                                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                    const emptyRow = document.createElement('tr');
                                    emptyRow.innerHTML = '<td colspan="6" class="py-6 text-center text-sm text-slate-500">Tidak ada laptop yang sedang dipinjam.</td>';
                                    tbody.appendChild(emptyRow);
                                }
                            }, 500);
                        } else {
                            showToast({ isError: true, errorMsg: data.message || 'Terjadi kesalahan.' });
                            btn.classList.remove('is-loading');
                            btn.querySelector('i').className = 'fas fa-undo-alt';
                        }
                    })
                    .catch(err => {
                        showToast({ isError: true, errorMsg: 'Gagal menghubungi server.' });
                        btn.classList.remove('is-loading');
                        btn.querySelector('i').className = 'fas fa-undo-alt';
                    });
                });

                // ── Activity section ──
                const ACTIVITY_URL = @json(route('dashboard.activity'));
                const dateFrom = document.getElementById('activity-date-from');
                const dateTo   = document.getElementById('activity-date-to');
                const countEl  = document.getElementById('activity-count');
                const tbody    = document.getElementById('activity-tbody');
                const mobileList = document.getElementById('activity-mobile-list');

                function todayStr() {
                    return new Date().toISOString().split('T')[0];
                }
                function weekStartStr() {
                    const d = new Date(); d.setDate(d.getDate() - d.getDay() + 1);
                    return d.toISOString().split('T')[0];
                }
                function monthStartStr() {
                    const d = new Date(); d.setDate(1);
                    return d.toISOString().split('T')[0];
                }

                function statusBadge(label, cls) {
                    return `<span class="rounded-full bg-${cls}-100 px-2 py-1 text-xs font-semibold text-${cls}-600">${label}</span>`;
                }

                function renderDesktopTable(data) {
                    if (!data.length) {
                        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-sm text-slate-400">Tidak ada data untuk rentang waktu ini.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(row => `
                        <tr>
                            <td class="text-xs text-slate-400 font-medium">${row.no}</td>
                            <td class="font-medium text-slate-700">${row.borrower_name}</td>
                            <td>${row.owner_name}</td>
                            <td class="text-xs">${row.borrowed_at}</td>
                            <td>${statusBadge(row.status_label, row.status_class)}</td>
                            <td class="text-xs">${row.returned_at}</td>
                            <td class="text-xs text-slate-500">${row.usage_purpose}</td>
                        </tr>
                    `).join('');
                }

                function renderMobileCards(data) {
                    if (!data.length) {
                        mobileList.innerHTML = '<div class="py-8 text-center text-sm text-slate-400">Tidak ada data untuk rentang waktu ini.</div>';
                        return;
                    }
                    mobileList.innerHTML = data.map((row, i) => `
                        <div class="activity-card">
                            <div class="activity-card-header" onclick="toggleActivityCard(this)">
                                <div class="activity-card-info">
                                    <div class="name">${row.borrower_name}</div>
                                    <div class="meta">Pemilik: ${row.owner_name} · ${row.borrowed_at}</div>
                                </div>
                                ${statusBadge(row.status_label, row.status_class)}
                                <button type="button" class="activity-expand-btn">+</button>
                            </div>
                            <div class="activity-card-details">
                                <div class="detail-row">
                                    <span class="detail-label">No</span>
                                    <span class="detail-value">${row.no}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Waktu Dipinjam</span>
                                    <span class="detail-value">${row.borrowed_at}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Waktu Dikembalikan</span>
                                    <span class="detail-value">${row.returned_at}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tujuan Pemakaian</span>
                                    <span class="detail-value">${row.usage_purpose}</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }

                window.toggleActivityCard = function(header) {
                    const details = header.nextElementSibling;
                    const btn = header.querySelector('.activity-expand-btn');
                    const isOpen = details.classList.contains('open');
                    details.classList.toggle('open');
                    btn.classList.toggle('expanded');
                };

                function loadActivity() {
                    const params = new URLSearchParams();
                    if (dateFrom.value) params.set('date_from', dateFrom.value);
                    if (dateTo.value)   params.set('date_to', dateTo.value);

                    tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-sm text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</td></tr>';
                    mobileList.innerHTML = '<div class="py-8 text-center text-sm text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</div>';

                    fetch(`${ACTIVITY_URL}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(json => {
                        const data = json.data || [];
                        countEl.textContent = data.length + ' catatan';
                        renderDesktopTable(data);
                        renderMobileCards(data);
                    })
                    .catch(() => {
                        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-sm text-rose-400">Gagal memuat data.</td></tr>';
                        mobileList.innerHTML = '<div class="py-8 text-center text-sm text-rose-400">Gagal memuat data.</div>';
                    });
                }

                // Preset buttons
                document.querySelectorAll('.activity-preset-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.activity-preset-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');

                        const range = this.dataset.range;
                        if (range === 'today') {
                            dateFrom.value = todayStr();
                            dateTo.value = todayStr();
                        } else if (range === 'week') {
                            dateFrom.value = weekStartStr();
                            dateTo.value = todayStr();
                        } else if (range === 'month') {
                            dateFrom.value = monthStartStr();
                            dateTo.value = todayStr();
                        } else {
                            dateFrom.value = '';
                            dateTo.value = '';
                        }
                        loadActivity();
                    });
                });

                // Custom filter button
                document.getElementById('activity-filter-btn').addEventListener('click', function() {
                    document.querySelectorAll('.activity-preset-btn').forEach(b => b.classList.remove('active'));
                    loadActivity();
                });

                // Initial load: today
                dateFrom.value = todayStr();
                dateTo.value = todayStr();
                loadActivity();
            });
        </script>
    @endif
@endsection
