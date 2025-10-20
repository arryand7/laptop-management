<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Services\BorrowService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    public function create()
    {
        $activeBorrowList = BorrowTransaction::with(['laptop.owner', 'student'])
            ->where('status', 'borrowed')
            ->orderByDesc('borrowed_at')
            ->limit(15)
            ->get();

        return view('staff.return.create', [
            'activeBorrowList' => $activeBorrowList,
        ]);
    }

    public function store(Request $request, BorrowService $borrowService)
    {
        $validated = $request->validate([
            'laptop_qr' => ['required', 'string'],
            'staff_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $laptopIdentifier = trim($validated['laptop_qr']);

        $laptop = Laptop::with(['owner', 'borrowTransactions' => fn ($query) => $query->where('status', 'borrowed')->latest('borrowed_at')])
            ->where(function ($query) use ($laptopIdentifier) {
                $query->where('qr_code', $laptopIdentifier)
                    ->orWhere('code', $laptopIdentifier)
                    ->orWhere('serial_number', $laptopIdentifier)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($laptopIdentifier)]);
            })
            ->orWhereHas('owner', function ($query) use ($laptopIdentifier) {
                $query->where('student_number', $laptopIdentifier);
            })
            ->first();

        if (!$laptop) {
            $laptop = Laptop::with('owner')
                ->where('code', 'like', "%{$laptopIdentifier}%")
                ->orWhere('name', 'like', "%{$laptopIdentifier}%")
                ->orWhere('serial_number', 'like', "%{$laptopIdentifier}%")
                ->orWhereHas('owner', function ($query) use ($laptopIdentifier) {
                    $query->where('student_number', 'like', "%{$laptopIdentifier}%");
                })
                ->first();
        }

        if (!$laptop) {
            return back()->withErrors(['laptop_qr' => 'Laptop tidak ditemukan. Gunakan kode, nama, serial, NIS pemilik, atau QR yang valid.'])->withInput();
        }

        $transaction = $laptop->borrowTransactions->first();

        if (!$transaction) {
            $transaction = BorrowTransaction::with('student')
                ->where('laptop_id', $laptop->id)
                ->where('status', 'borrowed')
                ->latest('borrowed_at')
                ->first();
        }

        if (!$transaction) {
            return back()->withErrors(['laptop_qr' => 'Tidak ditemukan catatan peminjaman aktif untuk laptop ini.'])->withInput();
        }

        $transaction = $borrowService->checkin(
            transaction: $transaction,
            staff: $request->user(),
            staffNotes: $validated['staff_notes'] ?? null,
        );

        $message = $transaction->was_late
            ? 'Pengembalian selesai. Status TERLAMBAT, pelanggaran otomatis ditambahkan.'
            : 'Pengembalian selesai. Terima kasih.';

        debug_event('Staff:Return', 'Pengembalian selesai', [
            'transaction' => $transaction->transaction_code,
            'status' => $transaction->status,
            'was_late' => $transaction->was_late,
        ]);

        return redirect()
            ->route('staff.return.create')
            ->with('status', $message);
    }

    public function quickReturn(Request $request, BorrowService $borrowService, BorrowTransaction $transaction)
    {
        if ($transaction->status !== 'borrowed') {
            return back()->withErrors('Transaksi sudah tidak aktif.')->withInput();
        }

        $borrowService->checkin(
            transaction: $transaction,
            staff: $request->user(),
            staffNotes: $request->input('staff_notes')
        );

        return back()->with('status', "Laptop {$transaction->laptop?->code} berhasil ditandai dikembalikan.");
    }
}
