@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-slate-800">Manajemen User</h1>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-sm">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama/email"
                    class="w-48 rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                <button type="submit" class="rounded bg-slate-900 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700">
                    Cari
                </button>
            </form>
            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <i class="fas fa-plus"></i> Tambah Data
            </a>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm datatable-default w-100">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Telepon</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-600">
                @foreach($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">
                            <div class="flex items-center gap-3">
                                <img src="{{ $user->avatar_url }}" alt="Avatar" class="h-10 w-10 rounded-full border border-slate-200 object-cover">
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-600">Aktif</span>
                            @else
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->phone ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-500">Ubah</a>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-500">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @if($users->isEmpty())
        <p class="mt-3 text-center text-sm text-slate-500">Belum ada data user admin/staff.</p>
    @endif
@endsection
