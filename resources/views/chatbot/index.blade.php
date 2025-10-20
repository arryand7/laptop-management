@extends('layouts.app')

@section('title', 'Chatbot Peminjaman')

@section('content')
    <div class="max-w-4xl space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-800">Chatbot Peminjaman</h1>
            <p class="mt-2 text-sm text-slate-500">Gunakan perintah singkat seperti <code>pinjam 20231023 LPT-AX45</code> atau <code>kembalikan 20231023</code>. Sistem akan menampilkan ringkasan sebelum eksekusi.</p>
        </div>

        <div id="chat-container" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div id="chat-log" class="space-y-4 overflow-y-auto" style="max-height: 420px;">
                <div class="flex gap-3">
                    <div class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="fas fa-robot"></i></div>
                    <div class="rounded-2xl rounded-tl-none bg-slate-100 px-4 py-3 text-sm text-slate-700">
                        Hai! Ketik perintah pinjam atau kembalikan. Saya akan tampilkan ringkasan dan meminta konfirmasi sebelum memproses.
                    </div>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4">
                <form id="chat-form" class="flex gap-3">
                    @csrf
                    <input type="text" id="chat-input" name="command" placeholder="Contoh: pinjam 20231023 LPT-AX45" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Kirim</button>
                </form>
                <div id="confirmation-actions" class="mt-4 hidden items-center justify-end gap-2">
                    <button id="cancel-confirmation" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batalkan</button>
                    <button id="confirm-command" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const log = document.getElementById('chat-log');
        const confirmActions = document.getElementById('confirmation-actions');
        const confirmButton = document.getElementById('confirm-command');
        const cancelButton = document.getElementById('cancel-confirmation');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let pendingConfirmationToken = null;
        let typingIndicator = null;

        const showTyping = () => {
            if (typingIndicator) { return; }
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'flex gap-3';
            typingIndicator.innerHTML = `<div class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="fas fa-robot"></i></div><div class="rounded-2xl rounded-tl-none bg-slate-100 px-4 py-3 text-sm text-slate-500 italic">Sedang mengetik...</div>`;
            log.appendChild(typingIndicator);
            log.scrollTop = log.scrollHeight;
        };

        const hideTyping = () => {
            if (typingIndicator) {
                typingIndicator.remove();
                typingIndicator = null;
            }
        };

        const appendMessage = (type, content) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex gap-3 ' + (type === 'user' ? 'justify-end' : '');

            const bubble = document.createElement('div');
            bubble.className = 'rounded-2xl px-4 py-3 text-sm max-w-xl break-words';

            if (type === 'user') {
                bubble.classList.add('rounded-tr-none', 'bg-blue-600', 'text-white', 'shadow');
            } else {
                bubble.classList.add('rounded-tl-none', 'bg-slate-100', 'text-slate-700');
            }

            if (typeof content === 'string') {
                bubble.innerHTML = content;
            } else {
                bubble.appendChild(content);
            }

            if (type !== 'user') {
                const icon = document.createElement('div');
                icon.className = 'h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600';
                icon.innerHTML = '<i class="fas fa-robot"></i>';
                wrapper.appendChild(icon);
                wrapper.appendChild(bubble);
            } else {
                wrapper.appendChild(bubble);
            }

            log.appendChild(wrapper);
            log.scrollTop = log.scrollHeight;
        };

        const showConfirmation = () => {
            confirmActions.classList.remove('hidden');
        };

        const hideConfirmation = () => {
            confirmActions.classList.add('hidden');
            pendingConfirmationToken = null;
        };

        const renderSummary = (summary) => {
            const container = document.createElement('div');
            container.className = 'space-y-3';

            const intent = summary.intent === 'borrow' ? 'Peminjaman' : 'Pengembalian';
            const header = document.createElement('p');
            header.className = 'font-semibold text-slate-800';
            header.textContent = `Ringkasan ${intent}`;
            container.appendChild(header);

            const student = document.createElement('p');
            student.className = 'text-sm text-slate-600';
            student.textContent = `Siswa: ${summary.student.name} (${summary.student.nis})${summary.student.classroom ? ' · ' + summary.student.classroom : ''}`;
            container.appendChild(student);

            const laptop = document.createElement('p');
            laptop.className = 'text-sm text-slate-600';
            laptop.textContent = `Laptop: ${summary.laptop.code ?? '-'} · ${summary.laptop.name ?? '-'}`;
            container.appendChild(laptop);

            if (summary.intent === 'borrow') {
                const due = document.createElement('p');
                due.className = 'text-sm text-slate-600';
                due.textContent = `Jatuh tempo default: ${summary.due_at}`;
                container.appendChild(due);

                const notes = document.createElement('p');
                notes.className = 'text-xs text-slate-500';
                notes.textContent = summary.notes ?? '';
                container.appendChild(notes);
            } else {
                const info = document.createElement('p');
                info.className = 'text-sm text-slate-600';
                info.textContent = `Dipinjam: ${summary.borrowed_at} · Batas: ${summary.due_at}`;
                container.appendChild(info);

                if (summary.is_late) {
                    const warning = document.createElement('p');
                    warning.className = 'text-xs font-semibold text-rose-600';
                    warning.textContent = 'Perhatian: pengembalian terlambat, sistem akan mencatat pelanggaran.';
                    container.appendChild(warning);
                }
            }

            return container;
        };

        const renderChoices = (message, choices) => {
            const container = document.createElement('div');
            container.className = 'space-y-2';

            const title = document.createElement('p');
            title.className = 'text-sm text-slate-600';
            title.textContent = message;
            container.appendChild(title);

            choices.forEach(choice => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50';
                button.textContent = `${choice.label} — klik untuk gunakan`;
                button.addEventListener('click', () => {
                    input.value = choice.command;
                    hideConfirmation();
                    pendingConfirmationToken = null;
                    form.requestSubmit();
                });
                container.appendChild(button);
            });

            return container;
        };

        const renderAnalysis = (reply, suggestions = []) => {
            const container = document.createElement('div');
            container.className = 'space-y-3';

            reply.split(/\n+/).forEach((paragraph) => {
                const trimmed = paragraph.trim();
                if (!trimmed) { return; }
                const p = document.createElement('p');
                p.className = 'text-sm text-slate-600';
                p.textContent = trimmed;
                container.appendChild(p);
            });

            if (suggestions.length) {
                const chips = document.createElement('div');
                chips.className = 'flex flex-wrap gap-2';
                suggestions.forEach((suggestion) => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100';
                    chip.textContent = suggestion;
                    chip.addEventListener('click', () => {
                        input.value = suggestion;
                        hideConfirmation();
                        pendingConfirmationToken = null;
                        form.requestSubmit();
                    });
                    chips.appendChild(chip);
                });
                container.appendChild(chips);
            }

            return container;
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const command = input.value.trim();
            if (!command) {
                return;
            }

            appendMessage('user', command);
            hideConfirmation();
            showTyping();

            try {
                const response = await fetch("{{ route('chatbot.preview') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ command }),
                });

                const data = await response.json();
                if (!response.ok || data.status === 'error') {
                    hideTyping();
                    appendMessage('bot', data.message ?? 'Terjadi kesalahan saat memproses perintah.');
                    return;
                }

                if (data.status === 'choices') {
                    hideTyping();
                    appendMessage('bot', renderChoices(data.message, data.choices ?? []));
                    return;
                }

                if (data.status === 'analysis') {
                    hideTyping();
                    appendMessage('bot', renderAnalysis(data.reply ?? '', data.suggestions ?? []));
                    return;
                }

                pendingConfirmationToken = data.confirmation_token;
                hideTyping();
                appendMessage('bot', renderSummary(data.summary));
                appendMessage('bot', 'Konfirmasi untuk melanjutkan?');
                showConfirmation();
            } catch (error) {
                appendMessage('bot', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
            } finally {
                hideTyping();
                input.value = '';
                input.focus();
            }
        });

        confirmButton.addEventListener('click', async () => {
            if (!pendingConfirmationToken) {
                return;
            }

            confirmButton.disabled = true;
            try {
                const response = await fetch("{{ route('chatbot.commit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token: pendingConfirmationToken }),
                });

                const data = await response.json();
                if (!response.ok || data.status === 'error') {
                    appendMessage('bot', data.message ?? 'Konfirmasi gagal diproses.');
                    return;
                }

                const result = data.result;
                const container = document.createElement('div');
                container.className = 'space-y-2';
                container.innerHTML = `<p class="font-semibold text-slate-800">Berhasil ${result.intent === 'borrow' ? 'mencatat peminjaman' : 'mengembalikan'}.</p>` +
                    `<p class="text-sm text-slate-600">Transaksi: ${result.transaction_code}</p>` +
                    (result.due_at ? `<p class="text-sm text-slate-600">Jatuh tempo: ${result.due_at}</p>` : '') +
                    (result.returned_at ? `<p class="text-sm text-slate-600">Dikembalikan: ${result.returned_at}</p>` : '');

                appendMessage('bot', container);
            } catch (error) {
                appendMessage('bot', 'Terjadi kesalahan saat memproses konfirmasi.');
            } finally {
                confirmButton.disabled = false;
                hideConfirmation();
            }
        });

        cancelButton.addEventListener('click', () => {
            appendMessage('bot', 'Baik, perintah dibatalkan.');
            hideConfirmation();
        });
    </script>
@endpush
