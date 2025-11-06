<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Laptop;
use App\Models\User;
use App\Services\BorrowService;
use App\Services\SanctionService;
use App\Support\AppSettingManager;
use App\Support\LendingDueDateResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BorrowController extends Controller
{
    public function create()
    {
        $setting = AppSettingManager::current();
        $defaultDueAt = LendingDueDateResolver::resolve(now(), $setting);
        $defaultDueLabel = LendingDueDateResolver::describe($setting);

        return view('staff.borrow.create', [
            'defaultDueAt' => $defaultDueAt,
            'defaultDueLabel' => $defaultDueLabel,
        ]);
    }

    public function store(Request $request, BorrowService $borrowService, SanctionService $sanctionService)
    {
        $validated = $request->validate([
            'student_qr' => ['required', 'string'],
            'laptop_qr' => ['required', 'string'],
            'usage_purpose' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date', 'after:now'],
            'staff_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $identifier = trim($validated['student_qr']);

        $student = User::students()
            ->where(function ($query) use ($identifier) {
                $query->where('qr_code', $identifier)
                    ->orWhere('card_code', $identifier)
                    ->orWhere('student_number', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($identifier)]);
            })
            ->first();

        if (!$student) {
            $student = User::students()
                ->where('name', 'like', "%{$identifier}%")
                ->orderBy('name')
                ->first();
        }

        if (!$student) {
            return back()->withErrors(['student_qr' => 'Siswa tidak ditemukan. Gunakan NIS, nama, kode kartu, atau QR yang valid.'])->withInput();
        }

        $sanctionService->refresh($student);
        if ($student->hasActiveSanction()) {
            $sanctionEnds = $student->sanction_ends_at
                ? $student->sanction_ends_at->translatedFormat('d M Y H:i')
                : '-';

            return back()->withErrors([
                'student_qr' => 'Siswa sedang dalam masa sanksi hingga ' . $sanctionEnds,
            ])->withInput();
        }

        if (!$student->is_active) {
            return back()->withErrors(['student_qr' => 'Akun siswa dinonaktifkan.'])->withInput();
        }

        $laptopIdentifier = trim($validated['laptop_qr']);

        $laptop = Laptop::with('owner')
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

        if ($laptop->status !== 'available') {
            $statusMessages = [
                'borrowed' => "Laptop {$laptop->code} sedang dipinjam dan belum dikembalikan.",
                'maintenance' => "Laptop {$laptop->code} sedang dalam perawatan/maintenance.",
                'retired' => "Laptop {$laptop->code} sedang DINONAKTIFKAN karena pelanggaran dan tidak dapat dipinjam.",
            ];

            $message = $statusMessages[$laptop->status] ?? 'Laptop sedang tidak tersedia untuk dipinjam.';

            return back()->withErrors(['laptop_qr' => $message])->withInput();
        }

        if ($laptop->is_missing) {
            return back()->withErrors([
                'laptop_qr' => "Laptop {$laptop->code} dilaporkan hilang dalam checklist terakhir dan tidak dapat dipinjam.",
            ])->withInput();
        }

        $dueAt = Carbon::parse($validated['due_at']);

        $transaction = $borrowService->checkout(
            student: $student,
            laptop: $laptop,
            staff: $request->user(),
            usagePurpose: $validated['usage_purpose'],
            dueAt: $dueAt,
            staffNotes: $validated['staff_notes'] ?? null,
        );

        debug_event('Staff:Borrow', 'Peminjaman berhasil', [
            'transaction' => $transaction->transaction_code,
        ]);

        return redirect()
            ->route('staff.borrow.create')
            ->with('status', 'Peminjaman berhasil dicatat dengan kode ' . $transaction->transaction_code);
    }
}
