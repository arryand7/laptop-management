<?php

namespace App\Services;

use App\Models\Sanction;
use App\Models\User;

class SanctionService
{
    public function apply(User $student, int $sanctionDays, ?User $issuedBy = null, string $reason = 'Pelanggaran keterlambatan pengembalian laptop'): Sanction
    {
        $startsAt = now();
        $endsAt = now()->addDays($sanctionDays);

        $sanction = Sanction::create([
            'user_id' => $student->id,
            'issued_by' => $issuedBy?->id,
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $reason,
        ]);

        $student->update([
            'sanction_ends_at' => $endsAt,
        ]);

        debug_event('SanctionService', 'Sanksi diterapkan', [
            'student' => $student->student_number,
            'ends_at' => $endsAt->toIso8601String(),
        ]);

        return $sanction;
    }

    public function refresh(User $student): void
    {
        if (!$student->sanction_ends_at) {
            return;
        }

        if (now()->greaterThanOrEqualTo($student->sanction_ends_at)) {
            $student->update([
                'sanction_ends_at' => null,
            ]);

            $student->sanctions()
                ->where('status', 'active')
                ->where('ends_at', '<=', now())
                ->update(['status' => 'expired']);

            debug_event('SanctionService', 'Sanksi berakhir otomatis', [
                'student' => $student->student_number,
            ]);
        }
    }
}
