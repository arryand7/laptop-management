<?php

namespace App\Support\Chatbot;

use App\Models\BorrowTransaction;
use App\Models\ChatConfirmationToken;
use App\Models\Laptop;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CommitService
{
    public function __construct(private BorrowService $borrowService)
    {
    }

    public function commit(User $actor, string $token): array
    {
        /** @var ChatConfirmationToken|null $record */
        $record = ChatConfirmationToken::where('token', $token)->where('user_id', $actor->id)->first();
        if (!$record) {
            throw new InvalidArgumentException('Token konfirmasi tidak valid atau sudah digunakan.');
        }

        if ($record->isExpired()) {
            $record->delete();
            throw new InvalidArgumentException('Token konfirmasi telah kedaluwarsa. Silakan ulangi perintah.');
        }

        $intent = $record->intent;
        $payload = $record->payload ?? [];

        try {
            if ($intent === 'borrow') {
                $result = $this->commitBorrow($actor, $payload);
            } elseif ($intent === 'return') {
                $result = $this->commitReturn($actor, $payload);
            } else {
                throw new InvalidArgumentException('Intent tidak dikenal.');
            }
        } finally {
            $record->delete();
        }

        return $result;
    }

    protected function commitBorrow(User $actor, array $payload): array
    {
        $student = User::students()->find($payload['student_id'] ?? null);
        $laptop = Laptop::find($payload['laptop_id'] ?? null);
        $dueAt = isset($payload['due_at']) ? Carbon::parse($payload['due_at']) : now()->addDay();

        if (!$student || !$laptop) {
            throw new InvalidArgumentException('Data konfirmasi tidak lagi berlaku. Ulangi perintah.');
        }

        if ($actor->isStudent() && $actor->id !== $student->id) {
            throw new InvalidArgumentException('Anda tidak memiliki izin untuk memproses siswa lain.');
        }

        $usagePurpose = 'Perintah chatbot oleh ' . $actor->name;

        $transaction = $this->borrowService->checkout(
            student: $student,
            laptop: $laptop,
            staff: $actor,
            usagePurpose: $usagePurpose,
            dueAt: $dueAt,
            staffNotes: null,
        );

        debug_event('Chatbot', 'Peminjaman diproses via chatbot', [
            'actor' => $actor->id,
            'student' => $student->id,
            'laptop' => $laptop->id,
            'transaction' => $transaction->transaction_code,
        ]);

        return [
            'intent' => 'borrow',
            'transaction_code' => $transaction->transaction_code,
            'due_at' => $transaction->due_at?->translatedFormat('d M Y H:i'),
            'student' => [
                'name' => $student->name,
                'nis' => $student->student_number,
            ],
            'laptop' => [
                'code' => $laptop->code,
                'name' => $laptop->name,
            ],
        ];
    }

    protected function commitReturn(User $actor, array $payload): array
    {
        $transaction = BorrowTransaction::with('student', 'laptop')->find($payload['transaction_id'] ?? null);
        if (!$transaction || $transaction->status !== 'borrowed') {
            throw new InvalidArgumentException('Transaksi tidak ditemukan atau sudah diproses.');
        }

        $student = $transaction->student;
        if ($actor->isStudent() && $student && $student->id !== $actor->id) {
            throw new InvalidArgumentException('Anda tidak memiliki izin untuk memproses siswa lain.');
        }

        $result = $this->borrowService->checkin($transaction, $actor, null);

        debug_event('Chatbot', 'Pengembalian diproses via chatbot', [
            'actor' => $actor->id,
            'transaction' => $result->transaction_code,
        ]);

        return [
            'intent' => 'return',
            'transaction_code' => $result->transaction_code,
            'returned_at' => $result->returned_at?->translatedFormat('d M Y H:i'),
            'student' => [
                'name' => $student?->name,
                'nis' => $student?->student_number,
            ],
            'laptop' => [
                'code' => $result->laptop?->code,
                'name' => $result->laptop?->name,
            ],
        ];
    }
}
