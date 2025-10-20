<?php

namespace App\Support\Chatbot;

use InvalidArgumentException;

class CommandParser
{
    public static function parse(string $command): array
    {
        $command = trim(preg_replace('/\s+/', ' ', $command));
        if ($command === '') {
            throw new InvalidArgumentException('Perintah tidak boleh kosong.');
        }

        $parts = explode(' ', $command);
        $intent = strtolower(array_shift($parts));

        if (!in_array($intent, ['pinjam', 'kembalikan'], true)) {
            throw new InvalidArgumentException('Perintah harus diawali dengan "pinjam" atau "kembalikan".');
        }

        $studentIdentifier = null;
        $targetIdentifier = null;

        foreach ($parts as $index => $part) {
            $clean = trim($part);
            if ($clean === '') {
                continue;
            }
            $lower = strtolower($clean);
            if (str_starts_with($lower, 'nis:')) {
                $clean = substr($clean, 4);
            } elseif (str_starts_with($lower, 'laptop:')) {
                $clean = substr($clean, 7);
            } elseif (str_starts_with($lower, 'kode:')) {
                $clean = substr($clean, 5);
            }
            if ($studentIdentifier === null) {
                $studentIdentifier = $clean;
            } elseif ($targetIdentifier === null) {
                $targetIdentifier = $clean;
            }
        }

        if (!$studentIdentifier) {
            throw new InvalidArgumentException('NIS siswa wajib dicantumkan setelah kata perintah.');
        }

        if ($intent === 'pinjam' && !$targetIdentifier) {
            throw new InvalidArgumentException('Kode laptop atau NIS pemilik wajib dicantumkan untuk perintah pinjam.');
        }

        return [
            'intent' => $intent === 'pinjam' ? 'borrow' : 'return',
            'student_identifier' => $studentIdentifier,
            'target_identifier' => $targetIdentifier,
        ];
    }
}
