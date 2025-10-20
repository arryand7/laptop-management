<?php

namespace App\Services;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Models\Violation;
use App\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class BorrowService
{
    public function __construct(protected SanctionService $sanctionService)
    {
    }

    public function checkout(User $student, Laptop $laptop, User $staff, string $usagePurpose, \DateTimeInterface $dueAt, ?string $staffNotes = null): BorrowTransaction
    {
        debug_event('BorrowService', 'Memulai proses peminjaman', [
            'student' => $student->student_number,
            'laptop' => $laptop->code,
            'staff' => $staff->email,
        ]);

        return DB::transaction(function () use ($student, $laptop, $staff, $usagePurpose, $dueAt, $staffNotes) {
            $transaction = BorrowTransaction::create([
                'transaction_code' => CodeGenerator::transactionCode(),
                'student_id' => $student->id,
                'laptop_id' => $laptop->id,
                'staff_id' => $staff->id,
                'usage_purpose' => $usagePurpose,
                'borrowed_at' => now(),
                'due_at' => $dueAt,
                'status' => 'borrowed',
                'was_late' => false,
                'staff_notes' => $staffNotes,
            ]);

            $laptop->markBorrowed();

            debug_event('BorrowService', 'Peminjaman tersimpan', [
                'transaction' => $transaction->transaction_code,
                'due_at' => $transaction->due_at?->toIso8601String(),
            ]);

            return $transaction;
        });
    }

    public function checkin(BorrowTransaction $transaction, User $staff, ?string $staffNotes = null): BorrowTransaction
    {
        debug_event('BorrowService', 'Memulai proses pengembalian', [
            'transaction' => $transaction->transaction_code,
        ]);

        return DB::transaction(function () use ($transaction, $staff, $staffNotes) {
            $transaction->refresh();

            $transaction->fill([
                'staff_notes' => $staffNotes ?? $transaction->staff_notes,
            ]);

            $transaction->markAsReturned($staff);
            $transaction->laptop?->markAvailable();

            if ($transaction->was_late) {
                $student = $transaction->student;
                $student->increment('violations_count');
                $student->refresh();

                Violation::create([
                    'user_id' => $student->id,
                    'borrow_transaction_id' => $transaction->id,
                    'points' => 1,
                    'notes' => 'Pengembalian terlambat',
                    'occurred_at' => now(),
                ]);

                debug_event('BorrowService', 'Pelanggaran dicatat', [
                    'student' => $student->student_number,
                    'total_violations' => $student->violations_count,
                ]);

                $violationLimit = config('lending.violation_limit', 3);
                if ($student->violations_count >= $violationLimit) {
                    $sanctionDays = config('lending.sanction_length_days', 7);
                    $this->sanctionService->apply(
                        student: $student,
                        sanctionDays: $sanctionDays,
                        issuedBy: $staff,
                        reason: "Mencapai {$violationLimit} pelanggaran keterlambatan"
                    );
                    debug_event('BorrowService', 'Sanksi diterapkan', [
                        'student' => $student->student_number,
                        'sanction_days' => $sanctionDays,
                    ]);
                }
            } else {
                $this->sanctionService->refresh($transaction->student);
            }

            debug_event('BorrowService', 'Pengembalian selesai', [
                'transaction' => $transaction->transaction_code,
                'status' => $transaction->status,
                'was_late' => $transaction->was_late,
            ]);

            return $transaction;
        });
    }
}
