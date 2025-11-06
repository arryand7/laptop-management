<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Services\BorrowService;
use App\Services\SanctionService;
use App\Support\AppSettingManager;
use App\Support\LendingDueDateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LaptopTransactionController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $setting = AppSettingManager::current();
        $defaultDueAt = LendingDueDateResolver::resolve(now(), $setting);
        $defaultDueLabel = LendingDueDateResolver::describe($setting);

        $recentTransactions = BorrowTransaction::with(['student', 'laptop'])
            ->orderByDesc('borrowed_at')
            ->limit(5)
            ->get();

        return view('staff.transactions.index', [
            'defaultDueAtIso' => $defaultDueAt->toIso8601String(),
            'defaultDueAtDisplay' => $defaultDueAt->translatedFormat('d M Y H:i'),
            'defaultDueLabel' => $defaultDueLabel,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function preview(Request $request, SanctionService $sanctionService): JsonResponse
    {
        $validated = $request->validate([
            'student_qr' => ['required', 'string', 'max:255'],
            'laptop_qr' => ['required', 'string', 'max:255'],
        ]);

        $student = $this->resolveStudent($validated['student_qr']);
        if (!$student) {
            return $this->respondError('Siswa tidak ditemukan. Periksa kembali QR atau identitas yang dipindai.');
        }

        $sanctionService->refresh($student);

        if ($student->hasActiveSanction()) {
            $endsAt = $student->sanction_ends_at?->translatedFormat('d M Y H:i');
            return $this->respondError("Siswa sedang dalam masa sanksi hingga {$endsAt}.", [
                'sanction_ends_at' => $student->sanction_ends_at?->toIso8601String(),
            ]);
        }

        if (!$student->is_active) {
            return $this->respondError('Akun siswa dinonaktifkan. Hubungi admin untuk mengaktifkan.');
        }

        $laptop = $this->resolveLaptop($validated['laptop_qr']);
        if (!$laptop) {
            return $this->respondError('Laptop tidak ditemukan. Pastikan QR/kode yang dipindai sudah benar.');
        }

        $activeBorrow = $this->findActiveBorrow($laptop);

        if ($laptop->status === 'available') {
            $setting = AppSettingManager::current();
            $dueAt = LendingDueDateResolver::resolve(now(), $setting);

            return response()->json([
                'status' => 'ok',
                'mode' => 'borrow',
                'student' => $this->presentStudent($student),
                'laptop' => $this->presentLaptop($laptop),
                'due_at' => $dueAt->toIso8601String(),
                'due_at_display' => $dueAt->translatedFormat('d M Y H:i'),
                'due_label' => LendingDueDateResolver::describe($setting),
            ]);
        }

        if ($laptop->status === 'borrowed') {
            if ($activeBorrow && $activeBorrow->student_id === $student->id) {
                return response()->json([
                    'status' => 'ok',
                    'mode' => 'return',
                    'student' => $this->presentStudent($student),
                    'laptop' => $this->presentLaptop($laptop),
                    'borrow_transaction' => [
                        'transaction_code' => $activeBorrow->transaction_code,
                        'borrowed_at' => $activeBorrow->borrowed_at?->toIso8601String(),
                        'borrowed_at_display' => $activeBorrow->borrowed_at?->translatedFormat('d M Y H:i'),
                        'due_at' => $activeBorrow->due_at?->toIso8601String(),
                        'due_at_display' => $activeBorrow->due_at?->translatedFormat('d M Y H:i'),
                    ],
                ]);
            }

            $borrower = $activeBorrow?->student?->name;
            $borrowerClass = $activeBorrow?->student?->classroom;
            return $this->respondError(
                $borrower
                    ? "Laptop sedang dipinjam oleh {$borrower}" . ($borrowerClass ? " ({$borrowerClass})" : '') . '.'
                    : 'Laptop sedang dipinjam oleh siswa lain.'
            );
        }

        $statusMessages = [
            'maintenance' => "Laptop {$laptop->code} sedang dalam perawatan.",
            'retired' => "Laptop {$laptop->code} tidak dapat dipinjam karena status dinonaktifkan.",
        ];

        $message = $statusMessages[$laptop->status] ?? 'Laptop sedang tidak dapat diproses.';

        return $this->respondError($message);
    }

    public function confirm(
        Request $request,
        BorrowService $borrowService,
        SanctionService $sanctionService
    ): JsonResponse {
        $validated = $request->validate([
            'student_qr' => ['required', 'string', 'max:255'],
            'laptop_qr' => ['required', 'string', 'max:255'],
            'usage_purpose' => ['nullable', 'string', 'max:255'],
            'staff_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $student = $this->resolveStudent($validated['student_qr']);
        if (!$student) {
            return $this->respondError('Siswa tidak ditemukan. Periksa kembali QR atau identitas yang dipindai.');
        }

        $sanctionService->refresh($student);

        if ($student->hasActiveSanction()) {
            $endsAt = $student->sanction_ends_at?->translatedFormat('d M Y H:i');
            return $this->respondError("Siswa sedang dalam masa sanksi hingga {$endsAt}.");
        }

        if (!$student->is_active) {
            return $this->respondError('Akun siswa dinonaktifkan. Hubungi admin untuk mengaktifkan.');
        }

        $laptop = $this->resolveLaptop($validated['laptop_qr']);
        if (!$laptop) {
            return $this->respondError('Laptop tidak ditemukan. Pastikan QR/kode yang dipindai sudah benar.');
        }

        $activeBorrow = $this->findActiveBorrow($laptop);

        if ($laptop->status === 'available') {
            $usagePurpose = trim((string) ($validated['usage_purpose'] ?? ''));
            if ($usagePurpose === '') {
                throw ValidationException::withMessages([
                    'usage_purpose' => 'Keperluan penggunaan wajib diisi untuk mencatat peminjaman.',
                ]);
            }

            $setting = AppSettingManager::current();
            $dueAt = LendingDueDateResolver::resolve(now(), $setting);

            $transaction = $borrowService->checkout(
                student: $student,
                laptop: $laptop,
                staff: $request->user(),
                usagePurpose: $usagePurpose,
                dueAt: $dueAt,
                staffNotes: $validated['staff_notes'] ?? null,
            );

            return response()->json([
                'status' => 'success',
                'mode' => 'borrow',
                'message' => 'Peminjaman berhasil dicatat.',
                'transaction_code' => $transaction->transaction_code,
                'borrowed_at' => $transaction->borrowed_at?->toIso8601String(),
                'borrowed_at_display' => $transaction->borrowed_at?->translatedFormat('d M Y H:i'),
                'due_at' => $transaction->due_at?->toIso8601String(),
                'due_at_display' => $transaction->due_at?->translatedFormat('d M Y H:i'),
            ]);
        }

        if ($laptop->status === 'borrowed') {
            if (!$activeBorrow || $activeBorrow->student_id !== $student->id) {
                return $this->respondError('Laptop sedang dipinjam oleh siswa lain.');
            }

            $transaction = $borrowService->checkin(
                transaction: $activeBorrow,
                staff: $request->user(),
                staffNotes: $validated['staff_notes'] ?? null,
            );

            $message = $transaction->was_late
                ? 'Pengembalian berhasil, tercatat sebagai terlambat.'
                : 'Pengembalian berhasil dicatat.';

            return response()->json([
                'status' => 'success',
                'mode' => 'return',
                'message' => $message,
                'transaction_code' => $transaction->transaction_code,
                'was_late' => $transaction->was_late,
                'borrowed_at' => $transaction->borrowed_at?->toIso8601String(),
                'borrowed_at_display' => $transaction->borrowed_at?->translatedFormat('d M Y H:i'),
                'due_at' => $transaction->due_at?->toIso8601String(),
                'due_at_display' => $transaction->due_at?->translatedFormat('d M Y H:i'),
                'returned_at' => $transaction->returned_at?->toIso8601String(),
                'returned_at_display' => $transaction->returned_at?->translatedFormat('d M Y H:i'),
            ]);
        }

        $statusMessages = [
            'maintenance' => "Laptop {$laptop->code} sedang dalam perawatan.",
            'retired' => "Laptop {$laptop->code} tidak dapat dipinjam karena status dinonaktifkan.",
        ];

        $message = $statusMessages[$laptop->status] ?? 'Laptop sedang tidak dapat diproses.';

        return $this->respondError($message);
    }

    private function resolveStudent(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $student = User::students()
            ->where(function ($query) use ($identifier) {
                $lower = Str::lower($identifier);
                $query->where('qr_code', $identifier)
                    ->orWhere('card_code', $identifier)
                    ->orWhere('student_number', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [$lower]);
            })
            ->first();

        if ($student) {
            return $student;
        }

        return User::students()
            ->where(function ($query) use ($identifier) {
                $like = '%' . $identifier . '%';
                $query->where('student_number', 'like', $like)
                    ->orWhere('name', 'like', $like);
            })
            ->orderBy('name')
            ->first();
    }

    private function resolveLaptop(string $identifier): ?Laptop
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $exact = Laptop::with('owner')
            ->where(function ($query) use ($identifier) {
                $lower = Str::lower($identifier);
                $query->where('qr_code', $identifier)
                    ->orWhere('code', $identifier)
                    ->orWhere('serial_number', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [$lower]);
            })
            ->orWhereHas('owner', function ($query) use ($identifier) {
                $query->where('student_number', $identifier);
            })
            ->first();

        if ($exact) {
            return $exact;
        }

        return Laptop::with('owner')
            ->where(function ($query) use ($identifier) {
                $like = '%' . $identifier . '%';
                $lower = Str::lower($identifier);
                $query->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('serial_number', 'like', $like)
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . $lower . '%']);
            })
            ->orWhereHas('owner', function ($query) use ($identifier) {
                $query->where('student_number', 'like', '%' . $identifier . '%');
            })
            ->orderBy('code')
            ->first();
    }

    private function findActiveBorrow(Laptop $laptop): ?BorrowTransaction
    {
        return BorrowTransaction::with('student')
            ->where('laptop_id', $laptop->getKey())
            ->where('status', 'borrowed')
            ->latest('borrowed_at')
            ->first();
    }

    private function presentStudent(User $student): array
    {
        return [
            'name' => $student->name,
            'student_number' => $student->student_number,
            'classroom' => $student->classroom,
        ];
    }

    private function presentLaptop(Laptop $laptop): array
    {
        return [
            'code' => $laptop->code,
            'name' => $laptop->name,
            'status' => $laptop->status,
        ];
    }

    private function respondError(string $message, array $payload = [], int $status = 422): JsonResponse
    {
        return response()->json(array_merge([
            'status' => 'error',
            'message' => $message,
        ], $payload), $status);
    }
}
