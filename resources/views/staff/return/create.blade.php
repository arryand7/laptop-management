@extends('layouts.app')

@section('title', 'Form Pengembalian')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">Pengembalian Laptop</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Pengembalian</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-xl font-semibold text-slate-800">Form Pengembalian Laptop</h1>
        <p class="mt-1 text-sm text-slate-500">Scan atau ketik identitas laptop.</p>

        <form action="{{ route('staff.return.store') }}" method="POST" class="mt-6 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="relative">
                <label class="block text-sm font-medium text-slate-600" for="laptop_qr">Identitas Laptop</label>
                <input type="text" id="laptop_qr" name="laptop_qr" value="{{ old('laptop_qr') }}" required autofocus class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="Scan/ketik kode/QR laptop" data-lookup="laptops" data-endpoint="{{ route('staff.lookup.laptops', ['only_borrowed' => 1]) }}" data-helper="laptop-helper" data-suggestions="laptop-suggestions" data-next="staff_notes">
                <div id="laptop-suggestions" class="lookup-suggestions d-none"></div>
                <p id="laptop-helper" data-default="Mulai ketik untuk menampilkan daftar laptop." class="mt-1 text-xs text-slate-500">Mulai ketik untuk menampilkan daftar laptop.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600" for="staff_notes">Catatan Kondisi (opsional)</label>
                <input type="text" id="staff_notes" name="staff_notes" value="{{ old('staff_notes') }}" placeholder="Contoh: Charger lengkap" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Catat Pengembalian</button>
        </form>
    </div>

    @if(isset($activeBorrowList))
        <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Laptop yang Sedang Dipinjam</h3>
                <span class="text-xs text-slate-400">{{ $activeBorrowList->count() }} catatan</span>
            </div>
            <div class="mt-4 table-responsive">
                <table class="table table-striped table-bordered table-sm datatable-default w-100">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="pb-2">Kode Transaksi</th>
                            <th class="pb-2">Laptop</th>
                            <th class="pb-2">Pemilik Laptop</th>
                            <th class="pb-2">Dipinjam Oleh</th>
                            <th class="pb-2">Dipinjam</th>
                            <th class="pb-2">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600">
                        @forelse($activeBorrowList as $transaction)
                            <tr>
                                <td class="py-2 font-mono text-xs text-slate-500">{{ $transaction->transaction_code }}</td>
                                <td class="py-2 text-slate-600">{{ $transaction->laptop?->code }} • {{ $transaction->laptop?->name }}</td>
                                <td class="py-2 text-slate-600">
                                    @if($transaction->laptop?->owner)
                                        {{ $transaction->laptop->owner->name }} ({{ $transaction->laptop->owner->student_number }})
                                    @else
                                        <span class="text-slate-400">Belum ditetapkan</span>
                                    @endif
                                </td>
                                <td class="py-2 text-slate-600">{{ $transaction->student?->name }} ({{ $transaction->student?->student_number }})</td>
                                <td class="py-2 text-slate-600">{{ $transaction->borrowed_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="py-2 text-slate-600">{{ $transaction->due_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">Belum ada laptop yang dipinjam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const helper = document.getElementById('laptop-helper');
            if (helper && !document.getElementById('laptop_qr').value) {
                helper.textContent = helper.dataset.default;
            }
        });
    </script>
@endpush
