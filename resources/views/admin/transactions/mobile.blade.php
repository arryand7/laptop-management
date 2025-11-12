@extends('layouts.app')

@section('title', 'Transaksi Mobile')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <header class="space-y-2">
            <h1 class="text-2xl font-semibold text-slate-800">Transaksi Laptop (Mobile)</h1>
            <p class="text-sm text-slate-500">Optimalkan peminjaman dan pengembalian langsung dari perangkat genggam. Gunakan tombol kamera untuk memindai QR.</p>
        </header>

        <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3">
                <label for="mobile-student-input" class="text-sm font-semibold text-slate-600">Scan Identitas Siswa</label>
                <div class="flex flex-col gap-2">
                    <input id="mobile-student-input" type="text" inputmode="text" placeholder="Scan/ketik NIS atau nama siswa" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-scan-target="mobile-student-input" class="scan-btn inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                            <i class="fas fa-camera"></i> Kamera
                        </button>
                        <button type="button" data-clear="mobile-student-input" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-50">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <label for="mobile-laptop-input" class="text-sm font-semibold text-slate-600">Scan Identitas Laptop</label>
                <div class="flex flex-col gap-2">
                    <input id="mobile-laptop-input" type="text" inputmode="text" placeholder="Scan/ketik kode laptop" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-scan-target="mobile-laptop-input" class="scan-btn inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                            <i class="fas fa-camera"></i> Kamera
                        </button>
                        <button type="button" data-clear="mobile-laptop-input" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-50">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div id="mobile-feedback" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
        </section>

        <section id="mobile-transaction-card"></section>

        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-700">Transaksi Terbaru</h2>
                <span class="text-xs text-slate-400">{{ $recentTransactions->count() }} catatan</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentTransactions as $transaction)
                    <div class="rounded-2xl border border-slate-100 px-4 py-3 text-sm text-slate-600">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>{{ $transaction->borrowed_at?->diffForHumans() ?? '-' }}</span>
                            <span>{{ strtoupper($transaction->transaction_code) }}</span>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $transaction->student?->name ?? '-' }} • {{ $transaction->laptop?->code }}</p>
                        <p class="text-xs text-slate-500">{{ $transaction->laptop?->name }}</p>
                        <div class="mt-2 text-xs">
                            @if($transaction->status === 'borrowed')
                                <span class="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-600">Borrowed</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-1 font-semibold text-emerald-600">
                                    Returned{{ $transaction->was_late ? ' (Late)' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada transaksi baru.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div id="camera-overlay" class="fixed inset-0 z-50 hidden bg-slate-900/90">
        <div class="absolute inset-0 flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 text-white">
                <p class="text-sm font-semibold">Arahkan kamera ke QR Code</p>
                <button type="button" id="camera-close" class="rounded-full border border-white/40 px-3 py-1 text-xs font-semibold">Tutup</button>
            </div>
            <div class="flex-1 px-4 pb-6">
                <video id="camera-video" class="h-full w-full rounded-2xl border border-white/20 object-cover"></video>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const studentInput = document.getElementById('mobile-student-input');
            const laptopInput = document.getElementById('mobile-laptop-input');
            const transactionFeedback = document.getElementById('mobile-feedback');
            const transactionCard = document.getElementById('mobile-transaction-card');
            const previewUrl = @json(route('admin.transactions.mobile.preview'));
            const confirmUrl = @json(route('admin.transactions.mobile.confirm'));

            const scanButtons = document.querySelectorAll('.scan-btn');
            const clearButtons = document.querySelectorAll('[data-clear]');
            const cameraOverlay = document.getElementById('camera-overlay');
            const cameraVideo = document.getElementById('camera-video');
            const cameraClose = document.getElementById('camera-close');

            let state = {
                student: '',
                laptop: '',
                mode: null,
                studentMeta: null,
                laptopMeta: null,
            };
            let previewTimer = null;
            let isLoading = false;
            let mediaStream = null;
            let barcodeDetector = 'BarcodeDetector' in window ? new BarcodeDetector({ formats: ['qr_code', 'code_128'] }) : null;
            let activeInput = null;

            const clearFeedback = () => {
                transactionFeedback.classList.add('hidden');
                transactionFeedback.textContent = '';
            };

            const showFeedback = (message) => {
                transactionFeedback.textContent = message;
                transactionFeedback.classList.remove('hidden');
            };

            const resetCard = () => {
                transactionCard.innerHTML = '';
                state = { ...state, mode: null, studentMeta: null, laptopMeta: null };
            };

            const updateStateAndPreview = () => {
                clearTimeout(previewTimer);
                if (!state.student || !state.laptop) {
                    resetCard();
                    return;
                }
                previewTimer = setTimeout(runPreview, 250);
            };

            const runPreview = () => {
                if (isLoading || !state.student || !state.laptop) {
                    return;
                }

                isLoading = true;
                clearFeedback();

                axios.post(previewUrl, {
                    student_qr: state.student,
                    laptop_qr: state.laptop,
                }).then((response) => {
                    const payload = response.data;
                    if (payload.status === 'ok') {
                        state.mode = payload.mode;
                        state.studentMeta = payload.student;
                        state.laptopMeta = payload.laptop;
                        renderCard(payload);
                    } else {
                        resetCard();
                        showFeedback(payload.message || 'Data tidak dapat diproses.');
                    }
                }).catch((error) => {
                    const message = error.response?.data?.message ?? 'Terjadi kesalahan.';
                    showFeedback(message);
                    resetCard();
                }).finally(() => {
                    isLoading = false;
                });
            };

            const renderCard = (payload) => {
                if (!payload.student || !payload.laptop) {
                    resetCard();
                    return;
                }

                if (payload.mode === 'borrow') {
                    transactionCard.innerHTML = renderBorrowCard(payload);
                } else if (payload.mode === 'return') {
                    transactionCard.innerHTML = renderReturnCard(payload);
                }

                const confirmButton = transactionCard.querySelector('[data-action="confirm"]');
                if (confirmButton) {
                    confirmButton.addEventListener('click', submitTransaction);
                }
            };

            const submitTransaction = () => {
                if (!state.mode) return;

                const payload = {
                    student_qr: state.student,
                    laptop_qr: state.laptop,
                    staff_notes: document.getElementById('mobile-staff-notes')?.value ?? null,
                };

                if (state.mode === 'borrow') {
                    payload.usage_purpose = document.getElementById('mobile-usage-purpose')?.value ?? '';
                }

                axios.post(confirmUrl, payload)
                    .then((response) => {
                        if (response.data.status === 'success') {
                            showFeedback(response.data.message || 'Transaksi berhasil.');
                            state.student = '';
                            state.laptop = '';
                            studentInput.value = '';
                            laptopInput.value = '';
                            resetCard();
                            studentInput.focus();
                        } else {
                            showFeedback(response.data.message || 'Transaksi gagal.');
                        }
                    })
                    .catch((error) => {
                        const message = error.response?.data?.message ?? 'Transaksi gagal.';
                        showFeedback(message);
                    });
            };

            const renderBorrowCard = (payload) => {
                return `
                    <div class="rounded-3xl border border-blue-100 bg-white p-4 shadow-sm space-y-3">
                        <div>
                            <p class="text-xs text-slate-400 uppercase">Mode</p>
                            <p class="text-sm font-semibold text-blue-600">Peminjaman</p>
                        </div>
                        ${renderInfoRows(payload)}
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500">Keperluan</label>
                            <input type="text" id="mobile-usage-purpose" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Contoh: Ujian CBT">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500">Catatan Petugas</label>
                            <input type="text" id="mobile-staff-notes" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Opsional">
                        </div>
                        <button type="button" data-action="confirm" class="w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-blue-500/20">Konfirmasi Peminjaman</button>
                    </div>
                `;
            };

            const renderReturnCard = (payload) => {
                return `
                    <div class="rounded-3xl border border-emerald-100 bg-white p-4 shadow-sm space-y-3">
                        <div>
                            <p class="text-xs text-slate-400 uppercase">Mode</p>
                            <p class="text-sm font-semibold text-emerald-600">Pengembalian</p>
                        </div>
                        ${renderInfoRows(payload)}
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500">Catatan Petugas</label>
                            <input type="text" id="mobile-staff-notes" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" placeholder="Opsional">
                        </div>
                        <button type="button" data-action="confirm" class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-emerald-500/20">Konfirmasi Pengembalian</button>
                    </div>
                `;
            };

            const renderInfoRows = (payload) => {
                return `
                    <div class="grid gap-3 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600">
                        <div>
                            <p class="font-semibold text-slate-800">${payload.student.name}</p>
                            <p>${payload.student.student_number ?? '-'} • ${payload.student.classroom ?? '-'}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">${payload.laptop.code}</p>
                            <p>${payload.laptop.name}</p>
                        </div>
                        ${payload.mode === 'borrow'
                            ? `<div>
                                    <p class="text-xs uppercase text-slate-400">Batas Pengembalian</p>
                                    <p class="font-semibold text-slate-800">${payload.due_at_display ?? '-'}</p>
                                    <p class="text-[0.65rem] text-slate-500">${payload.due_label ?? ''}</p>
                                </div>`
                            : `<div>
                                    <p class="text-xs uppercase text-slate-400">Dipinjam</p>
                                    <p class="font-semibold text-slate-800">${payload.borrow_transaction.borrowed_at_display ?? '-'}</p>
                                    <p class="text-xs text-slate-500">Jatuh tempo: ${payload.borrow_transaction.due_at_display ?? '-'}</p>
                                </div>`
                        }
                    </div>
                `;
            };

            clearButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = document.getElementById(button.dataset.clear);
                    if (!target) return;
                    target.value = '';
                    state = { ...state, [button.dataset.clear.includes('student') ? 'student' : 'laptop']: '' };
                    target.focus();
                    updateStateAndPreview();
                });
            });

            studentInput.addEventListener('input', (event) => {
                state.student = event.target.value.trim();
                updateStateAndPreview();
            });

            laptopInput.addEventListener('input', (event) => {
                state.laptop = event.target.value.trim();
                updateStateAndPreview();
            });

            const startCamera = async (targetId) => {
                if (!barcodeDetector) {
                    alert('Perangkat tidak mendukung pemindaian menggunakan kamera.');
                    return;
                }

                activeInput = document.getElementById(targetId);

                try {
                    mediaStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment' },
                    });
                    cameraVideo.srcObject = mediaStream;
                    await cameraVideo.play();
                    cameraOverlay.classList.remove('hidden');
                    scanLoop();
                } catch (error) {
                    alert('Tidak dapat mengakses kamera. Pastikan izin telah diberikan.');
                }
            };

            const stopCamera = () => {
                if (mediaStream) {
                    mediaStream.getTracks().forEach((track) => track.stop());
                    mediaStream = null;
                }
                cameraOverlay.classList.add('hidden');
            };

            const scanLoop = async () => {
                if (!mediaStream || cameraOverlay.classList.contains('hidden')) {
                    return;
                }
                try {
                    const barcodes = await barcodeDetector.detect(cameraVideo);
                    if (barcodes.length && activeInput) {
                        activeInput.value = barcodes[0].rawValue.trim();
                        if (activeInput === studentInput) {
                            state.student = activeInput.value;
                        } else {
                            state.laptop = activeInput.value;
                        }
                        stopCamera();
                        updateStateAndPreview();
                        return;
                    }
                } catch (error) {
                    console.error(error);
                }
                requestAnimationFrame(scanLoop);
            };

            scanButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.scanTarget;
                    if (targetId) {
                        startCamera(targetId);
                    }
                });
            });

            cameraClose.addEventListener('click', stopCamera);

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopCamera();
                }
            });
        });
    </script>
@endpush
