@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
        <h1 class="text-xl font-semibold text-slate-800 m-0">Data Siswa</h1>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Sync dari SSO (Bootstrap Button Group dengan AdminLTE Dropdown) -->
            <div class="btn-group shadow-sm">
                <button type="button" id="btn-sso-sync" class="btn btn-outline-secondary bg-white font-semibold inline-flex items-center gap-2" style="border-color: #cbd5e1; color: #334155 !important; font-size: 0.825rem; padding: 0.4rem 0.75rem;">
                    <i class="fas fa-sync-alt" id="sync-icon"></i>
                    <span id="sync-text">Sync dari SSO</span>
                </button>
                <button type="button" class="btn btn-outline-secondary bg-white dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-color: #cbd5e1; color: #334155 !important; font-size: 0.825rem; padding: 0.4rem 0.5rem;">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 rounded-xl p-1" style="font-size: 13px; z-index: 1050; min-width: 220px;">
                    <a class="dropdown-item js-sync-mode-option d-flex align-items-center py-2 px-3 rounded-lg" href="javascript:void(0)" data-mode="full">
                        <i class="fas fa-database text-primary mr-2 fa-lg"></i>
                        <div>
                            <strong class="d-block text-dark">Full Sync</strong>
                            <small class="text-muted">Tarik ulang seluruh data SSO</small>
                        </div>
                    </a>
                    <a class="dropdown-item js-sync-mode-option d-flex align-items-center py-2 px-3 rounded-lg" href="javascript:void(0)" data-mode="delta">
                        <i class="fas fa-bolt text-success mr-2 fa-lg"></i>
                        <div>
                            <strong class="d-block text-dark">Delta Sync (Cepat)</strong>
                            <small class="text-muted">Hanya data baru / diperbarui</small>
                        </div>
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.students.template') }}" class="btn btn-outline-secondary bg-white font-semibold inline-flex items-center gap-2 shadow-sm" style="border-color: #cbd5e1; color: #475569 !important; font-size: 0.825rem; padding: 0.4rem 0.75rem;">
                <i class="fas fa-file-download"></i> Template Import
            </a>
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs shadow-sm">
                @csrf
                <label class="font-semibold text-slate-600 mb-0">Import Excel</label>
                <input type="file" name="file" class="text-xs" accept=".xlsx,.xls" required>
                <input type="text" name="default_password" value="password" placeholder="Default password" class="w-32 rounded border border-slate-300 px-2 py-0.5 text-xs">
                <button type="submit" class="rounded bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-700">Upload</button>
            </form>
        </div>
    </div>

    <!-- Alert / Toast Container -->
    <div id="sso-sync-alert" class="hidden mt-3 mb-4 rounded-xl border p-4 text-sm transition-all duration-300"></div>

    @if(session('generated_password'))
        <div class="mt-3 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            Kata sandi sementara untuk siswa baru: <span class="font-mono font-bold">{{ session('generated_password') }}</span>
        </div>
    @endif

    <form method="GET" class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-end">
        <div class="flex-1">
            <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari siswa</label>
            <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Nama, NIS, atau kelas" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
    </form>

    @php $studentsFormId = 'students-bulk-form'; @endphp
    <form id="{{ $studentsFormId }}" class="js-bulk-form" action="{{ route('admin.students.bulk') }}" method="POST" data-table="#students-table" hidden>
        @csrf
    </form>

    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <select name="action" class="js-bulk-action rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required data-confirm-message="Yakin ingin menghapus siswa terpilih? Tindakan ini tidak bisa dibatalkan." data-confirm-action="delete" form="{{ $studentsFormId }}">
                <option value="">Pilih aksi</option>
                <option value="activate">Aktifkan</option>
                <option value="deactivate">Nonaktifkan</option>
                <option value="delete">Hapus</option>
            </select>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" form="{{ $studentsFormId }}">
                <i class="fas fa-check"></i> Terapkan
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-0">Centang data siswa yang ingin diubah statusnya.</p>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary inline-flex items-center gap-2 shadow-sm font-semibold" style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #ffffff !important; border: none; padding: 0.45rem 1rem;">
            <i class="fas fa-plus"></i> <span style="color: #ffffff !important;">Tambah Data</span>
        </a>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table id="students-table" class="table table-striped table-bordered table-sm datatable-default w-100 mb-0">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="select-checkbox px-4 py-3 text-center align-middle" data-orderable="false">
                        <input type="checkbox" class="js-select-all" data-target="#students-table">
                    </th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">NIS</th>
                    <th class="px-4 py-3">Kelas</th>
                    <th class="px-4 py-3">Jenis Kelamin</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Laptop Dimiliki</th>
                    <th class="px-4 py-3">Pelanggaran</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
                <tbody class="text-slate-600">
                @foreach($students as $student)
                    <tr>
                        <td class="px-4 py-3 text-center align-middle">
                            <input type="checkbox" class="js-row-checkbox" name="student_ids[]" value="{{ $student->id }}" form="{{ $studentsFormId }}">
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-800">
                            <div class="flex items-center gap-3">
                                <img src="{{ $student->avatar_url }}" alt="Avatar" class="h-10 w-10 rounded-full border border-slate-200 object-cover">
                                <span>{{ $student->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $student->student_number }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $student->classroom }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $student->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $student->owned_laptops_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $student->violations_count }}x</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($student->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Aktif</span>
                            @else
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.students.show', $student) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Detail</a>
                                <a href="{{ route('admin.students.edit', $student) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-500">Ubah</a>
                                <a href="{{ route('admin.students.qr', $student) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-500">QR</a>
                                <form action="{{ route('admin.students.destroy', $student) }}" method="POST" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-500">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($students->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Belum ada data siswa.</p>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnSync = document.getElementById('btn-sso-sync');
        const syncIcon = document.getElementById('sync-icon');
        const syncText = document.getElementById('sync-text');
        const alertBox = document.getElementById('sso-sync-alert');

        if (!btnSync) return;

        // Jalankan sync dengan mode tertentu
        async function runSync(mode = 'full') {
            const modeLabel = mode === 'delta' ? 'Delta Sync (hanya data baru/berubah)' : 'Full Sync (seluruh data)';
            if (!confirm(`Mulai sinkronisasi data dari SSO menggunakan mode: ${modeLabel}?`)) {
                return;
            }

            // Set loading state
            btnSync.disabled = true;
            btnSync.classList.add('opacity-70', 'disabled');
            if (syncIcon) syncIcon.classList.add('fa-spin');
            if (syncText) syncText.textContent = 'Menyinkronkan...';

            showAlert('info', 'Sedang menghubungi Server SSO dan memproses data...', false);

            try {
                const response = await fetch("{{ route('sso.sync') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ mode: mode })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert('success', data.message || 'Sinkronisasi berhasil diselesaikan!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Gagal menyinkronkan data dari SSO.');
                }
            } catch (error) {
                console.error('SSO Sync Error:', error);
                showAlert('danger', 'Terjadi kesalahan jaringan atau server SSO tidak dapat dihubungi.');
            } finally {
                btnSync.disabled = false;
                btnSync.classList.remove('opacity-70', 'disabled');
                if (syncIcon) syncIcon.classList.remove('fa-spin');
                if (syncText) syncText.textContent = 'Sync dari SSO';
            }
        }

        function showAlert(type, message, autoHide = true) {
            if (!alertBox) return;
            alertBox.className = 'mt-3 mb-4 rounded-xl border p-4 text-sm transition-all duration-300';

            if (type === 'success') {
                alertBox.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                alertBox.innerHTML = `<div class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-500"></i><span>${message}</span></div>`;
            } else if (type === 'danger') {
                alertBox.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-800');
                alertBox.innerHTML = `<div class="flex items-center gap-2"><i class="fas fa-exclamation-circle text-rose-500"></i><span>${message}</span></div>`;
            } else {
                alertBox.classList.add('border-blue-200', 'bg-blue-50', 'text-blue-800');
                alertBox.innerHTML = `<div class="flex items-center gap-2"><i class="fas fa-spinner fa-spin text-blue-500"></i><span>${message}</span></div>`;
            }

            alertBox.classList.remove('hidden');

            if (autoHide && type === 'danger') {
                setTimeout(() => alertBox.classList.add('hidden'), 8000);
            }
        }

        // Event listener klik tombol utama (default: Full Sync)
        btnSync.addEventListener('click', () => runSync('full'));

        // Event listener opsi dropdown
        document.querySelectorAll('.js-sync-mode-option').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const mode = this.dataset.mode || 'full';
                runSync(mode);
            });
        });
    });
</script>
@endpush
