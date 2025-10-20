<?php

namespace App\Support\Chatbot;

use App\Models\BorrowTransaction;
use App\Models\ChatConfirmationToken;
use App\Models\Laptop;
use App\Models\User;
use App\Services\SanctionService;
use App\Support\AppSettingManager;
use App\Support\LendingDueDateResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PreviewService
{
    public function build(User $actor, array $parsed): array
    {
        $student = $this->resolveStudent($actor, $parsed['student_identifier']);

        if ($parsed['intent'] === 'borrow') {
            return $this->buildBorrowPreview($actor, $student, $parsed['target_identifier']);
        }

        return $this->buildReturnPreview($actor, $student, $parsed['target_identifier']);
    }

    protected function resolveStudent(User $actor, string $identifier): User
    {
        $student = User::students()
            ->where(function ($query) use ($identifier) {
                $query->where('student_number', $identifier)
                    ->orWhere('qr_code', $identifier)
                    ->orWhere('card_code', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($identifier)]);
            })
            ->first();

        if (!$student) {
            throw new InvalidArgumentException('Siswa dengan identitas tersebut tidak ditemukan.');
        }

        if ($actor->isStudent() && $actor->id !== $student->id) {
            throw new InvalidArgumentException('Anda tidak memiliki izin untuk memproses siswa lain.');
        }

        return $student;
    }

    protected function buildBorrowPreview(User $actor, User $student, string $targetIdentifier): array
    {
        app(SanctionService::class)->refresh($student);

        if (!$student->is_active) {
            throw new InvalidArgumentException('Akun siswa dinonaktifkan.');
        }

        if ($student->hasActiveSanction()) {
            throw new InvalidArgumentException('Siswa sedang dalam masa sanksi dan tidak dapat meminjam laptop.');
        }

        $laptops = LaptopResolver::resolve($targetIdentifier);
        if ($laptops->isEmpty()) {
            throw new InvalidArgumentException('Laptop tidak ditemukan. Gunakan kode laptop atau NIS pemilik.');
        }

        if ($laptops->count() > 1) {
            return [
                'status' => 'choices',
                'message' => 'Ditemukan lebih dari satu laptop. Pilih salah satunya atau ketik ulang dengan kode laptop spesifik.',
                'choices' => $laptops->map(function (Laptop $laptop) use ($student) {
                    $command = sprintf('pinjam %s %s', $student->student_number, $laptop->code);
                    return [
                        'label' => $laptop->code . ' · ' . $laptop->name,
                        'command' => $command,
                    ];
                })->values(),
            ];
        }

        /** @var Laptop $laptop */
        $laptop = $laptops->first();

        if ($laptop->status !== 'available') {
            throw new InvalidArgumentException(sprintf('Laptop %s saat ini tidak tersedia (status: %s).', $laptop->code, ucfirst($laptop->status)));
        }

        $setting = AppSettingManager::current();
        $defaultDue = LendingDueDateResolver::resolve(now(), $setting);

        $summary = [
            'intent' => 'borrow',
            'student' => [
                'name' => $student->name,
                'nis' => $student->student_number,
                'classroom' => $student->classroom,
                'is_active' => $student->is_active,
            ],
            'laptop' => [
                'code' => $laptop->code,
                'name' => $laptop->name,
                'status' => $laptop->status,
            ],
            'due_at' => $defaultDue->translatedFormat('d M Y H:i'),
            'due_iso' => $defaultDue->toIso8601String(),
            'notes' => LendingDueDateResolver::describe($setting),
        ];

        $token = $this->storeToken($actor, [
            'intent' => 'borrow',
            'student_id' => $student->id,
            'laptop_id' => $laptop->id,
            'due_at' => $defaultDue->toIso8601String(),
        ]);

        return [
            'status' => 'ok',
            'summary' => $summary,
            'confirmation_token' => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
    }

    protected function buildReturnPreview(User $actor, User $student, ?string $targetIdentifier): array
    {
        app(SanctionService::class)->refresh($student);

        $query = BorrowTransaction::with('laptop')
            ->where('student_id', $student->id)
            ->where('status', 'borrowed');

        if ($targetIdentifier) {
            $query->whereHas('laptop', function ($q) use ($targetIdentifier) {
                $q->where('code', $targetIdentifier)
                    ->orWhere('qr_code', $targetIdentifier)
                    ->orWhere('serial_number', $targetIdentifier)
                    ->orWhereHas('owner', function ($owner) use ($targetIdentifier) {
                        $owner->where('student_number', $targetIdentifier);
                    });
            });
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            throw new InvalidArgumentException('Tidak ditemukan peminjaman aktif untuk siswa tersebut.');
        }

        if (!$targetIdentifier && $transactions->count() > 1) {
            return [
                'status' => 'choices',
                'message' => 'Siswa memiliki lebih dari satu peminjaman aktif. Pilih salah satunya.',
                'choices' => $transactions->map(function (BorrowTransaction $trx) use ($student) {
                    $command = sprintf('kembalikan %s %s', $student->student_number, $trx->laptop?->code ?? '');
                    return [
                        'label' => ($trx->laptop?->code ?? '-') . ' · ' . ($trx->laptop?->name ?? 'Laptop'),
                        'command' => $command,
                    ];
                })->values(),
            ];
        }

        /** @var BorrowTransaction $transaction */
        $transaction = $transactions->first();
        $laptop = $transaction->laptop;

        $late = $transaction->due_at ? now()->greaterThan($transaction->due_at) : false;

        $summary = [
            'intent' => 'return',
            'student' => [
                'name' => $student->name,
                'nis' => $student->student_number,
                'classroom' => $student->classroom,
            ],
            'laptop' => [
                'code' => $laptop?->code,
                'name' => $laptop?->name,
            ],
            'borrowed_at' => $transaction->borrowed_at?->translatedFormat('d M Y H:i') ?? '-',
            'due_at' => $transaction->due_at?->translatedFormat('d M Y H:i') ?? '-',
            'is_late' => $late,
        ];

        $token = $this->storeToken($actor, [
            'intent' => 'return',
            'transaction_id' => $transaction->id,
        ]);

        return [
            'status' => 'ok',
            'summary' => $summary,
            'confirmation_token' => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
    }

    protected function storeToken(User $actor, array $payload): ChatConfirmationToken
    {
        return ChatConfirmationToken::create([
            'token' => Str::uuid()->toString(),
            'user_id' => $actor->id,
            'intent' => $payload['intent'],
            'payload' => Arr::except($payload, ['intent']),
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);
    }
}
