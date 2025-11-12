@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-slate-800">Data Siswa</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.students.template') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                <i class="fas fa-file-download"></i> Template Import
            </a>
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-sm">
                @csrf
                <label class="font-semibold text-slate-600 mb-0">Import Excel</label>
                <input type="file" name="file" class="text-xs" accept=".xlsx,.xls" required>
                <input type="text" name="default_password" value="password" placeholder="Default password" class="w-36 rounded border border-slate-300 px-2 py-1 text-xs">
                <button type="submit" class="rounded bg-slate-900 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700">Upload</button>
            </form>
        </div>
    </div>

    @if(session('generated_password'))
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            Kata sandi sementara untuk siswa baru: <span class="font-mono">{{ session('generated_password') }}</span>
        </div>
    @endif

    <form method="GET" class="mt-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-end">
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

    <div class="mt-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
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
        <p class="text-xs text-slate-500">Centang data siswa yang ingin diubah statusnya.</p>
        <a href="{{ route('admin.students.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
            <i class="fas fa-plus"></i> Tambah Data
        </a>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table id="students-table" class="table table-striped table-bordered table-sm datatable-default w-100">
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
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $student->name }}</td>
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
