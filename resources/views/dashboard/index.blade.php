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
                                                <form action="{{ route('staff.return.quick', $activeTrx) }}" method="POST" onsubmit="return confirm('Tandai laptop {{ $laptop->code }} sudah dikembalikan?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded bg-emerald-500 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-600">
                                                        <i class="fas fa-undo-alt"></i> Dikembalikan
                                                    </button>
                                                </form>
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
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Aktivitas Terbaru</h3>
                        <span class="text-xs text-slate-400">5 catatan terakhir</span>
                    </div>
                    <div class="mt-4 table-responsive">
                        <table class="table table-striped table-bordered table-sm datatable-default w-100">
                            <thead class="text-xs uppercase text-slate-400">
                                <tr>
                                    <th class="pb-2">Kode Transaksi</th>
                                    <th class="pb-2">Siswa</th>
                                    <th class="pb-2">Laptop</th>
                                    <th class="pb-2">Tujuan Pemakaian</th>
                                    <th class="pb-2">Status</th>
                                    <th class="pb-2">Dipinjam</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-600">
                                @forelse($recentBorrowData as $transaction)
                                    <tr>
                                        <td class="py-2 font-mono text-xs text-slate-500">{{ $transaction->transaction_code }}</td>
                                        <td class="py-2 text-slate-600">{{ $transaction->student?->name }}</td>
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
                                        <td class="py-2 text-slate-600">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-sm text-slate-500">Belum ada data peminjaman.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const dailyCtx = document.getElementById('dailyBorrowChart');
                if (dailyCtx) {
                    new Chart(dailyCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: @json($dailyLabels),
                            datasets: [{
                                label: 'Peminjaman',
                                data: @json($dailyValues),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
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
            });
        </script>
    @endif
@endsection
