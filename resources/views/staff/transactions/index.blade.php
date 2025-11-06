@extends('layouts.app')

@section('title', 'Transaksi Laptop')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header>
            <h1 class="text-2xl font-semibold text-slate-800">Laptop Borrow/Return System</h1>
            <p class="mt-1 text-sm text-slate-500">
                Scan QR siswa dan QR laptop untuk mendeteksi otomatis apakah transaksi adalah peminjaman atau pengembalian.
            </p>
        </header>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="relative">
                    <label for="student-input" class="block text-sm font-semibold text-slate-600">Scan Student QR</label>
                    <div class="mt-2 flex items-center rounded-xl border border-slate-300 px-4 py-3 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/10">
                        <span class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                            <i class="fas fa-user text-base"></i>
                        </span>
                        <input id="student-input" type="text" autofocus
                               class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none"
                               placeholder="Scan/ketik NIS, nama, kode kartu, atau QR"
                               data-lookup="students"
                               data-endpoint="{{ route('staff.lookup.students') }}"
                               data-suggestions="transaction-student-suggestions"
                               data-helper="transaction-student-helper"
                               data-next="laptop-input">
                        <button type="button" data-clear="student-input"
                                class="ml-3 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500 hover:border-slate-300 hover:text-slate-600">
                            Reset
                        </button>
                    </div>
                    <div id="transaction-student-suggestions" class="lookup-suggestions d-none"></div>
                    <p id="transaction-student-helper" data-default="Mulai ketik untuk menampilkan daftar siswa."
                       class="mt-2 text-xs text-slate-400">Mulai ketik untuk menampilkan daftar siswa.</p>
                </div>
                <div class="relative">
                    <label for="laptop-input" class="block text-sm font-semibold text-slate-600">Scan Laptop QR</label>
                    <div class="mt-2 flex items-center rounded-xl border border-slate-300 px-4 py-3 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/10">
                        <span class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-500">
                            <i class="fas fa-laptop text-base"></i>
                        </span>
                        <input id="laptop-input" type="text"
                               class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none"
                               placeholder="Scan/ketik kode/QR laptop"
                               data-lookup="laptops"
                               data-endpoint="{{ route('staff.lookup.laptops') }}"
                               data-suggestions="transaction-laptop-suggestions"
                               data-helper="transaction-laptop-helper">
                        <button type="button" data-clear="laptop-input"
                                class="ml-3 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500 hover:border-slate-300 hover:text-slate-600">
                            Reset
                        </button>
                    </div>
                    <div id="transaction-laptop-suggestions" class="lookup-suggestions d-none"></div>
                    <p id="transaction-laptop-helper" data-default="Mulai ketik untuk menampilkan daftar laptop."
                       class="mt-2 text-xs text-slate-400">Mulai ketik untuk menampilkan daftar laptop.</p>
                </div>
            </div>

            <div id="transaction-feedback" class="mt-6 hidden rounded-2xl border border-rose-200 bg-white px-5 py-4 text-sm shadow-lg shadow-rose-200/40"></div>

            <div id="transaction-card" class="mt-6"></div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <header class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-700">Transaksi Terbaru</h2>
                    <p class="text-xs text-slate-400">Menampilkan 5 transaksi terakhir peminjaman/pengembalian.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">{{ count($recentTransactions) }} catatan</span>
            </header>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm" id="recent-table">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-2">Waktu</th>
                            <th class="py-2">Siswa</th>
                            <th class="py-2">Laptop</th>
                            <th class="py-2">Aksi</th>
                            <th class="py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-tbody" class="divide-y divide-slate-100 text-slate-600">
                        @forelse($recentTransactions as $transaction)
                            <tr>
                                <td class="py-2 font-mono text-xs text-slate-500">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="py-2">
                                    <div class="font-medium text-slate-700">{{ $transaction->student?->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $transaction->student?->student_number }}</div>
                                </td>
                                <td class="py-2">
                                    <div class="font-medium text-slate-700">{{ $transaction->laptop?->code }}</div>
                                    <div class="text-xs text-slate-400">{{ $transaction->laptop?->name }}</div>
                                </td>
                                <td class="py-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600">
                                        {{ ucfirst($transaction->status === 'borrowed' ? 'borrow' : 'return') }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    @if($transaction->status === 'borrowed')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-600">Sedang Dipinjam</span>
                                    @else
                                        @if($transaction->was_late)
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600">Dikembalikan (Late)</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600">Dikembalikan</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-500">Belum ada transaksi yang terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="toast-container" class="pointer-events-none fixed right-6 top-6 z-50 flex flex-col gap-2"></div>
@endsection

@push('styles')
    <style>
        .transaction-card {
            border-radius: 1.5rem;
            border-width: 1px;
            border-color: #e2e8f0;
            background: linear-gradient(145deg, #f8fafc, #ffffff);
            box-shadow: 0 18px 45px -20px rgba(30, 136, 229, 0.35);
        }
        .transaction-card .section-title {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 600;
            color: #94a3b8;
        }
        .toast-item {
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.25s ease;
        }
        .toast-item.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
            }

            const studentInput = document.getElementById('student-input');
            const laptopInput = document.getElementById('laptop-input');
            const transactionCard = document.getElementById('transaction-card');
            const transactionFeedback = document.getElementById('transaction-feedback');
            const recentTbody = document.getElementById('recent-tbody');
            const toastContainer = document.getElementById('toast-container');
            const studentHelper = document.getElementById('transaction-student-helper');
            const laptopHelper = document.getElementById('transaction-laptop-helper');

            const previewUrl = @json(route('staff.transactions.preview'));
            const confirmUrl = @json(route('staff.transactions.confirm'));

            const state = {
                student: '',
                laptop: '',
                mode: null,
                dueAt: null,
                borrowTransaction: null,
                studentMeta: null,
                laptopMeta: null,
            };

            let previewTimer = null;
            let isLoading = false;

            const resetHelper = (helper) => {
                if (!helper) {
                    return;
                }
                helper.textContent = helper.dataset.default ?? '';
            };

            const clearFieldErrors = () => {
                const usageInput = document.getElementById('usage-purpose');
                const usageError = document.getElementById('usage-purpose-error');

                if (usageError) {
                    usageError.textContent = '';
                    usageError.classList.add('hidden');
                }

                if (usageInput) {
                    usageInput.classList.remove('border-rose-400', 'focus:border-rose-500', 'focus:ring-rose-500/30');
                }
            };

            const displayFieldError = (field, message) => {
                if (field !== 'usage_purpose') {
                    return false;
                }

                const usageInput = document.getElementById('usage-purpose');
                const usageError = document.getElementById('usage-purpose-error');

                if (!usageInput || !usageError) {
                    return false;
                }

                usageError.textContent = message;
                usageError.classList.remove('hidden');

                usageInput.classList.add('border-rose-400', 'focus:border-rose-500', 'focus:ring-rose-500/30');
                usageInput.focus();

                return true;
            };

            const focusElement = (element) => {
                if (!element) {
                    return;
                }
                requestAnimationFrame(() => {
                    element.focus();
                    if (typeof element.select === 'function') {
                        element.select();
                    }
                });
            };

            const handleCardEnter = (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                submitTransaction();
            };

            const clearButtons = document.querySelectorAll('[data-clear]');
            clearButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const target = document.getElementById(button.dataset.clear);
                    if (target) {
                        target.value = '';
                        target.focus();
                        if (button.dataset.clear === 'student-input') {
                            state.student = '';
                            resetHelper(studentHelper);
                        } else {
                            state.laptop = '';
                            resetHelper(laptopHelper);
                        }
                        resetCard();
                    }
                });
            });

            studentInput.addEventListener('input', () => {
                state.student = studentInput.value.trim();
                schedulePreview();
            });

            laptopInput.addEventListener('input', () => {
                state.laptop = laptopInput.value.trim();
                schedulePreview();
            });

            studentInput.addEventListener('lookup:selected', (event) => {
                state.student = studentInput.value.trim();
                const item = event.detail?.item;
                if (item) {
                    state.studentMeta = {
                        name: item.name,
                        student_number: item.student_number,
                        classroom: item.classroom,
                    };
                }
                clearTimeout(previewTimer);
                schedulePreview();
            });

            laptopInput.addEventListener('lookup:selected', (event) => {
                state.laptop = laptopInput.value.trim();
                const item = event.detail?.item;
                if (item) {
                    state.laptopMeta = {
                        code: item.code,
                        name: item.name,
                        status: item.status,
                    };
                }
                clearTimeout(previewTimer);
                runPreview();
            });

            studentInput.addEventListener('keydown', captureEnter);
            laptopInput.addEventListener('keydown', captureEnter);

            function captureEnter(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    runPreview();
                }
            }

            function schedulePreview() {
                if (!state.student || !state.laptop) {
                    resetCard();
                    return;
                }
                clearTimeout(previewTimer);
                previewTimer = setTimeout(runPreview, 280);
            }

            function runPreview() {
                if (!state.student || !state.laptop) {
                    return;
                }

                if (isLoading) {
                    return;
                }

                isLoading = true;
                transactionFeedback.classList.add('hidden');
                transactionFeedback.textContent = '';

                axios.post(previewUrl, {
                    student_qr: state.student,
                    laptop_qr: state.laptop,
                })
                    .then(response => {
                        const payload = response.data;
                        if (payload.status === 'ok') {
                            state.mode = payload.mode;
                            state.dueAt = payload.due_at ?? null;
                            state.borrowTransaction = payload.borrow_transaction ?? null;
                            renderCard(payload);
                        } else {
                            showFeedback(payload.message || 'Tidak dapat menampilkan transaksi.');
                            resetCard();
                        }
                    })
                    .catch(error => {
                        const message = extractMessage(error) || 'Terjadi kesalahan saat memproses data.';
                        showFeedback(message);
                        resetCard();
                    })
                    .finally(() => {
                        isLoading = false;
                    });
            }

            function renderCard(payload) {
                const student = payload.student;
                const laptop = payload.laptop;

                if (!student || !laptop) {
                    resetCard();
                    return;
                }

                state.studentMeta = student;
                state.laptopMeta = laptop;

                if (payload.mode === 'borrow') {
                    transactionCard.innerHTML = renderBorrowCard(student, laptop, payload);
                } else if (payload.mode === 'return') {
                    transactionCard.innerHTML = renderReturnCard(student, laptop, payload);
                } else {
                    resetCard();
                }

                clearFieldErrors();

                const confirmButton = transactionCard.querySelector('[data-action="confirm"]');
                if (confirmButton) {
                    confirmButton.addEventListener('click', submitTransaction);
                }
                setupCardInteractions(payload);
            }

            function renderBorrowCard(student, laptop, payload) {
                const dueDisplay = payload.due_at_display ?? '-';
                const dueLabel = payload.due_label ?? '';
                const nowDisplay = new Date().toLocaleString();

                return `
                    <div class="transaction-card relative overflow-hidden p-6">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 via-sky-500 to-blue-600"></div>
                        <div class="flex flex-col gap-6 md:flex-row md:items-start">
                            <div class="flex-1 space-y-4">
                                <div>
                                    <div class="section-title">Student</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-800">${student.name}</div>
                                    <div class="text-sm text-slate-500">${student.student_number ?? '-'} ${student.classroom ? '• ' + student.classroom : ''}</div>
                                </div>
                                <div>
                                    <div class="section-title">Laptop</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-800">${laptop.code ?? '-'} • ${laptop.name ?? '-'}</div>
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-600 mt-2">Ready to Borrow</span>
                                </div>
                            </div>
                            <div class="flex-1 space-y-4 rounded-2xl bg-white/80 p-5 shadow-inner">
                                <div>
                                    <label class="section-title">Purpose of Use</label>
                                    <input type="text" id="usage-purpose" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Contoh: Ujian CBT Kelas XII">
                                    <p id="usage-purpose-error" class="mt-2 hidden text-xs font-medium text-rose-500"></p>
                                </div>
                                <div>
                                    <label class="section-title">Catatan Petugas (opsional)</label>
                                    <input type="text" id="staff-notes" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Contoh: Charger lengkap">
                                </div>
                                <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-600">
                                    <div>
                                        <dt class="section-title">Borrow Time</dt>
                                        <dd class="mt-1 font-medium text-slate-700">${nowDisplay}</dd>
                                    </div>
                                    <div>
                                        <dt class="section-title">Expected Return</dt>
                                        <dd class="mt-1 font-medium text-slate-700">${dueDisplay}</dd>
                                        <p class="mt-1 text-xs text-slate-400">${dueLabel}</p>
                                    </div>
                                </dl>
                                <button type="button" data-action="confirm"
                                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                    <i class="fas fa-check-circle text-base"></i>
                                    Confirm Borrow
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            function renderReturnCard(student, laptop, payload) {
                const transaction = payload.borrow_transaction || {};
                const nowDisplay = new Date().toLocaleString();
                const dueDisplay = transaction.due_at_display ?? '-';
                const borrowDisplay = transaction.borrowed_at_display ?? '-';

                return `
                    <div class="transaction-card relative overflow-hidden p-6">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-blue-500 to-sky-600"></div>
                        <div class="flex flex-col gap-6 md:flex-row md:items-start">
                            <div class="flex-1 space-y-4">
                                <div>
                                    <div class="section-title">Student</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-800">${student.name}</div>
                                    <div class="text-sm text-slate-500">${student.student_number ?? '-'} ${student.classroom ? '• ' + student.classroom : ''}</div>
                                </div>
                                <div>
                                    <div class="section-title">Laptop</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-800">${laptop.code ?? '-'} • ${laptop.name ?? '-'}</div>
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-600 mt-2">Return in Progress</span>
                                </div>
                            </div>
                            <div class="flex-1 space-y-4 rounded-2xl bg-white/80 p-5 shadow-inner">
                                <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-600">
                                    <div>
                                        <dt class="section-title">Borrowed At</dt>
                                        <dd class="mt-1 font-medium text-slate-700">${borrowDisplay}</dd>
                                    </div>
                                    <div>
                                        <dt class="section-title">Due At</dt>
                                        <dd class="mt-1 font-medium text-slate-700">${dueDisplay}</dd>
                                    </div>
                                </dl>
                                <div>
                                    <label class="section-title">Return Time</label>
                                    <p class="mt-1 text-sm font-medium text-slate-700">${nowDisplay}</p>
                                </div>
                                <div>
                                    <label class="section-title">Catatan Pengembalian (opsional)</label>
                                    <input type="text" id="staff-notes" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Contoh: Kondisi lengkap, charger aman">
                                </div>
                                <button type="button" data-action="confirm"
                                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                                    <i class="fas fa-redo text-base"></i>
                                    Confirm Return
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            function submitTransaction() {
                if (!state.mode) {
                    return;
                }

                clearFieldErrors();

                const payload = {
                    student_qr: state.student,
                    laptop_qr: state.laptop,
                    staff_notes: document.getElementById('staff-notes')?.value ?? null,
                };

                if (state.mode === 'borrow') {
                    payload.usage_purpose = document.getElementById('usage-purpose')?.value ?? '';
                }

                axios.post(confirmUrl, payload)
                    .then(response => {
                        const data = response.data;
                        if (data.status === 'success') {
                            showToast('success', data.message || 'Transaksi berhasil.');
                            appendRecentRow({
                                mode: data.mode,
                                student: state.studentMeta,
                                laptop: state.laptopMeta,
                                borrowed_at_display: data.borrowed_at_display ?? null,
                                returned_at_display: data.returned_at_display ?? null,
                                due_at_display: data.due_at_display ?? null,
                                was_late: data.was_late ?? false,
                            });
                            resetAll();
                        } else {
                            showToast('error', data.message || 'Transaksi gagal.');
                        }
                    })
                    .catch(error => {
                        const validationErrors = error.response?.status === 422 ? (error.response.data?.errors ?? null) : null;
                        let handled = false;

                        if (validationErrors) {
                            Object.entries(validationErrors).forEach(([field, messages]) => {
                                if (!Array.isArray(messages) || messages.length === 0) {
                                    return;
                                }
                                handled = displayFieldError(field, messages[0]) || handled;
                            });
                        }

                        if (!handled) {
                            const message = extractMessage(error) || 'Transaksi gagal diproses.';
                            showToast('error', message);
                            showFeedback(message);
                        } else {
                            transactionFeedback.classList.add('hidden');
                            transactionFeedback.textContent = '';
                        }

                        if (validationErrors) {
                            focusFirstError(Object.keys(validationErrors)[0]);
                        }
                    });
            }

            function extractMessage(error) {
                if (error.response?.data?.message) {
                    return error.response.data.message;
                }
                if (error.response?.data?.errors) {
                    const firstKey = Object.keys(error.response.data.errors)[0];
                    if (firstKey) {
                        return error.response.data.errors[firstKey][0];
                    }
                }
                return error.message;
            }

            function focusFirstError(field) {
                if (field === 'usage_purpose') {
                    document.getElementById('usage-purpose')?.focus();
                }
            }

            function resetCard() {
                state.mode = null;
                state.dueAt = null;
                state.borrowTransaction = null;
                state.studentMeta = null;
                state.laptopMeta = null;
                transactionCard.innerHTML = '';
                clearFieldErrors();
            }

            function resetAll() {
                studentInput.value = '';
                laptopInput.value = '';
                state.student = '';
                state.laptop = '';
                resetCard();
                transactionFeedback.classList.add('hidden');
                transactionFeedback.textContent = '';
                resetHelper(studentHelper);
                resetHelper(laptopHelper);
                studentInput.focus();
            }

            function showFeedback(message, type = 'error') {
                transactionFeedback.innerHTML = `
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full ${type === 'error' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'}">
                            <i class="${type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle'}"></i>
                        </span>
                        <div class="flex-1 text-sm font-medium text-slate-700">
                            ${message}
                        </div>
                        <button type="button" id="transaction-feedback-close" class="ml-2 text-xs font-semibold uppercase tracking-wide text-slate-400 transition hover:text-slate-600">
                            Tutup
                        </button>
                    </div>
                `;
                transactionFeedback.classList.remove('hidden');
                transactionFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById('transaction-feedback-close')?.addEventListener('click', () => {
                    transactionFeedback.classList.add('hidden');
                });
            }

            function showToast(type, message) {
                const wrapper = document.createElement('div');
                wrapper.className = `toast-item rounded-xl border px-4 py-3 text-sm font-medium shadow-lg ${
                    type === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                        : 'border-rose-200 bg-rose-50 text-rose-700'
                }`;
                wrapper.textContent = message;

                toastContainer.appendChild(wrapper);
                requestAnimationFrame(() => wrapper.classList.add('show'));

                setTimeout(() => {
                    wrapper.classList.remove('show');
                    setTimeout(() => wrapper.remove(), 250);
                }, 4200);
            }

            function appendRecentRow(meta) {
                if (!recentTbody) {
                    return;
                }

                const emptyRow = recentTbody.querySelector('[data-empty-row]');
                if (emptyRow) {
                    emptyRow.remove();
                }

                const timestamp = meta.mode === 'return'
                    ? (meta.returned_at_display || new Date().toLocaleString())
                    : (meta.borrowed_at_display || new Date().toLocaleString());

                const studentName = meta.student?.name ?? '-';
                const studentNumber = meta.student?.student_number ?? state.student;
                const laptopCode = meta.laptop?.code ?? '-';
                const laptopName = meta.laptop?.name ?? state.laptop;

                const actionLabel = meta.mode === 'borrow' ? 'Borrow' : 'Return';
                let statusBadge = '';

                if (meta.mode === 'borrow') {
                    statusBadge = '<span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-600">Sedang Dipinjam</span>';
                } else {
                    statusBadge = meta.was_late
                        ? '<span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600">Dikembalikan (Late)</span>'
                        : '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600">Dikembalikan</span>';
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="py-2 font-mono text-xs text-slate-500">${timestamp}</td>
                    <td class="py-2">
                        <div class="font-medium text-slate-700">${studentName}</div>
                        <div class="text-xs text-slate-400">${studentNumber}</div>
                    </td>
                    <td class="py-2">
                        <div class="font-medium text-slate-700">${laptopCode}</div>
                        <div class="text-xs text-slate-400">${laptopName}</div>
                    </td>
                    <td class="py-2">
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600">${actionLabel}</span>
                    </td>
                    <td class="py-2">${statusBadge}</td>
                `;

                recentTbody.prepend(row);

                const rows = recentTbody.querySelectorAll('tr');
                if (rows.length > 5) {
                    rows[rows.length - 1].remove();
                }
            }

            function setupCardInteractions(payload) {
                const confirmButton = transactionCard.querySelector('[data-action="confirm"]');
                const usageInput = document.getElementById('usage-purpose');
                const staffNotesInput = document.getElementById('staff-notes');

                if (payload.mode === 'borrow') {
                    if (usageInput) {
                        focusElement(usageInput);
                        usageInput.addEventListener('keydown', handleCardEnter);
                    }
                    if (staffNotesInput) {
                        staffNotesInput.addEventListener('keydown', handleCardEnter);
                    }
                } else if (payload.mode === 'return') {
                    if (staffNotesInput) {
                        focusElement(staffNotesInput);
                        staffNotesInput.addEventListener('keydown', handleCardEnter);
                    } else if (confirmButton) {
                        focusElement(confirmButton);
                    }
                }
            }
        });
    </script>
@endpush
