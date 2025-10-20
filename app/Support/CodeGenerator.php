<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CodeGenerator
{
    public static function studentQr(): string
    {
        return self::uniqueCode('users', 'qr_code', 'STU-' . Str::upper(Str::random(8)));
    }

    public static function laptopQr(): string
    {
        return self::uniqueCode('laptops', 'qr_code', 'LAP-' . Str::upper(Str::random(8)));
    }

    public static function laptopCode(): string
    {
        return self::uniqueCode('laptops', 'code', 'LP-' . Str::upper(Str::random(5)));
    }

    public static function transactionCode(): string
    {
        return self::uniqueCode('borrow_transactions', 'transaction_code', 'TRX-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)));
    }

    protected static function uniqueCode(string $table, string $column, string $candidate): string
    {
        while (DB::table($table)->where($column, $candidate)->exists()) {
            $candidate = preg_replace('/-[A-Z0-9]+$/', '', $candidate) . '-' . Str::upper(Str::random(4));
        }

        return $candidate;
    }
}
