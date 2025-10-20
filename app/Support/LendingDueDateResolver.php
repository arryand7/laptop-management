<?php

namespace App\Support;

use App\Models\AppSetting;
use Carbon\Carbon;

class LendingDueDateResolver
{
    public static function resolve(Carbon $reference, AppSetting $setting): Carbon
    {
        $mode = $setting->lending_due_mode ?? 'relative';
        $reference = $reference->copy();

        if ($mode === 'daily') {
            $timeString = $setting->lending_due_time ?? '16:00';
            [$hour, $minute] = self::splitTime($timeString);
            $due = $reference->copy()->setTime($hour, $minute, 0);
            if ($due->lessThanOrEqualTo($reference)) {
                $due->addDay();
            }
            return $due;
        }

        if ($mode === 'fixed') {
            $dateString = $setting->lending_due_date;
            if ($dateString) {
                $due = Carbon::parse($dateString);
                if ($due->greaterThan($reference)) {
                    return $due;
                }
            }
            return $reference->copy()->addDay();
        }

        $days = max((int) ($setting->lending_due_days ?? 1), 1);
        $due = $reference->copy()->addDays($days);
        if ($setting->lending_due_time) {
            [$hour, $minute] = self::splitTime($setting->lending_due_time);
            $due->setTime($hour, $minute, 0);
        }

        return $due;
    }

    public static function describe(AppSetting $setting): string
    {
        $mode = $setting->lending_due_mode ?? 'relative';

        if ($mode === 'daily') {
            $time = $setting->lending_due_time ?? '16:00';
            return "Default setiap hari pukul {$time}.";
        }

        if ($mode === 'fixed') {
            if ($setting->lending_due_date) {
                $formatted = Carbon::parse($setting->lending_due_date)->translatedFormat('d M Y H:i');
                return "Default tanggal {$formatted}.";
            }
            return 'Default mengikuti tanggal khusus yang ditetapkan.';
        }

        $days = max((int) ($setting->lending_due_days ?? 1), 1);
        $timePhrase = $setting->lending_due_time ? " pukul {$setting->lending_due_time}" : '';

        return "Default {$days} hari sejak peminjaman{$timePhrase}.";
    }

    private static function splitTime(string $time): array
    {
        $parts = array_map('intval', array_pad(explode(':', $time), 2, 0));
        return [$parts[0] % 24, $parts[1] % 60];
    }
}
