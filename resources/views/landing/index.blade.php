<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laptop Management') }}</title>
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="icon" href="{{ asset('images/logo-sabira.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-sabira.png') }}">
</head>
<body>
    <div class="landing-container">
        <header class="landing-header">
            <div class="landing-branding">
                <img src="{{ asset('images/logo-sabira.png') }}" alt="Logo">
                <span>{{ config('app.name', 'Laptop Management') }}</span>
            </div>
            <a href="{{ route('login') }}" class="landing-login">
                <i class="fas fa-lock"></i> Masuk
            </a>
        </header>

        <section class="landing-hero">
            <div>
                <h1>Platform Monitoring Laptop MA Unggul SABIRA</h1>
                <p>Kelola seluruh data inventaris, pantau peminjaman dan pelanggaran.</p>
            </div>

            <div class="landing-highlight-grid">
                <div class="landing-highlight-card">
                    <h3>Total Laptop Terdaftar</h3>
                    <div class="landing-highlight-number">{{ number_format($totalLaptops) }}</div>
                    <p class="landing-highlight-meta">Seluruh perangkat yang tercatat di sistem sekolah.</p>
                    <ul class="landing-list">
                        <li class="landing-list-item accent">
                            <strong>Laptop Putra</strong>
                            <span>{{ $laptopGenderCounts['male'] ?? 0 }} unit</span>
                        </li>
                        <li class="landing-list-item accent">
                            <strong>Laptop Putri</strong>
                            <span>{{ $laptopGenderCounts['female'] ?? 0 }} unit</span>
                        </li>
                    </ul>
                </div>
                <div class="landing-highlight-card">
                    <h3>Status Laptop Saat Ini</h3>
                    <div class="landing-highlight-number">{{ $statusSummary->sum('total') }}</div>
                    <p class="landing-highlight-meta">Distribusi unit berdasarkan kondisi terbaru.</p>
                    <ul class="landing-list">
                        @foreach($statusSummary as $summary)
                            <li class="landing-list-item accent">
                                <strong>{{ ucfirst($summary['status'] ?? 'unknown') }}</strong>
                                <span>{{ $summary['total'] }} unit</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="landing-highlight-card">
                    <h3>Jumlah Pelanggaran Aktif</h3>
                    <div class="landing-highlight-number">{{ $topViolators->sum('violations_count') }}</div>
                    <p class="landing-highlight-meta">Akumulasi pelanggaran yang masih aktif di sistem.</p>
                    <button class="landing-toggle-detail" data-target="detail-retired">
                        <i class="fas fa-angle-down"></i> Lihat laptop nonaktif
                    </button>
                    <div id="detail-retired" class="landing-detail">
                        <ul class="landing-detail-list">
                            @forelse($retiredLaptops as $laptop)
                                <li>
                                    <span>{{ $laptop->code }} · {{ $laptop->name }}</span>
                                    <span>{{ $laptop->owner?->name ?? 'Belum ada pemilik' }}</span>
                                </li>
                            @empty
                                <li>
                                    <span>Tidak ada laptop berstatus nonaktif.</span>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <h2>Data Laptop</h2>
            <div class="landing-grid">
                <div class="landing-card">
                    <h3>Data Peminjaman</h3>
                    <ul class="landing-list">
                        @forelse($borrowedLaptops as $item)
                            <li class="landing-list-item">
                                <strong>{{ $item->laptop?->name ?? 'Laptop' }} • {{ $item->laptop?->code }}</strong>
                                <span>Dipinjam oleh {{ $item->student?->name ?? 'Siswa' }}</span>
                                <span>Pinjam: {{ $item->borrowed_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                            </li>
                        @empty
                            <li class="landing-list-item">
                                <strong>Belum ada peminjaman aktif.</strong>
                            </li>
                        @endforelse
                    </ul>
                </div>
                <div class="landing-card">
                    <h3>Aktivitas Terakhir</h3>
                    <ul class="landing-list" style="margin-top:1.1rem;">
                        @forelse($recentActivities as $activity)
                            <li class="landing-list-item">
                                <strong>{{ $activity->transaction_code }}</strong>
                                <span>{{ $activity->student?->name ?? 'Siswa' }} &mdash; {{ $activity->laptop?->name ?? 'Laptop' }}</span>
                        <span>{{ ($activity->borrowed_at ?? $activity->created_at)?->translatedFormat('d M Y H:i') }}</span>
                            </li>
                        @empty
                            <li class="landing-list-item">
                                <strong>Belum ada aktivitas tercatat.</strong>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <h2>Top 5 Siswa Dengan Pelanggaran Terbanyak</h2>
            <div class="landing-card">
                <ul class="landing-violators">
                    @forelse($topViolators as $student)
                        <li class="landing-violator-item">
                            <div class="meta">
                                <strong>{{ $student->name }}</strong>
                                <span>{{ $student->student_number ?? 'NIS-' }} · {{ $student->classroom ?? 'Kelas?' }}</span>
                            </div>
                            <span class="badge">{{ $student->violations_count }} pelanggaran</span>
                        </li>
                    @empty
                        <li class="landing-violator-item">
                            <div class="meta">
                                <strong>Belum ada data pelanggaran.</strong>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="landing-section">
            <h2>Insight 30 Hari Terakhir</h2>
            <div class="landing-grid landing-charts">
                <div class="landing-card landing-chart-card">
                    <h3>Peminjaman Laptop</h3>
                    <div class="landing-chart-wrapper">
                        <canvas id="borrow-chart" aria-label="Grafik peminjaman"></canvas>
                    </div>
                </div>
                <div class="landing-card landing-chart-card">
                    <h3>Pelanggaran</h3>
                    <div class="landing-chart-wrapper">
                        <canvas id="violation-chart" aria-label="Grafik pelanggaran"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <footer class="landing-footer">
            &copy; {{ now()->year }} Sistem Peminjaman Laptop - <a href="https://www.linkedin.com/in/ryand-arifriantoni">Ryand Arifriantoni</a>
        </footer>
    </div>

    <script src="https://kit.fontawesome.com/4e5dfc6fe0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script>
        document.querySelectorAll('.landing-toggle-detail').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const target = document.getElementById(trigger.dataset.target);
                trigger.classList.toggle('active');
                target?.classList.toggle('active');
            });
        });

        const borrowSeries = @json($borrowSeries);
        const violationSeries = @json($violationSeries);

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                    grid: { display: false },
                },
                y: {
                    ticks: { color: '#64748b', precision: 0 },
                    grid: { color: 'rgba(226, 232, 240, 0.6)' },
                    beginAtZero: true,
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(148, 163, 184, 0.3)',
                    borderWidth: 1,
                },
            },
        };

        const borrowCtx = document.getElementById('borrow-chart');
        if (borrowCtx) {
            new Chart(borrowCtx, {
                type: 'line',
                data: {
                    labels: borrowSeries.map(item => item.label),
                    datasets: [{
                        data: borrowSeries.map(item => item.value),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.18)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                    }],
                },
                options: chartOptions,
            });
        }

        const violationCtx = document.getElementById('violation-chart');
        if (violationCtx) {
            new Chart(violationCtx, {
                type: 'bar',
                data: {
                    labels: violationSeries.map(item => item.label),
                    datasets: [{
                        data: violationSeries.map(item => item.value),
                        backgroundColor: 'rgba(239, 68, 68, 0.28)',
                        borderColor: '#ef4444',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }],
                },
                options: chartOptions,
            });
        }
    </script>
</body>
</html>
