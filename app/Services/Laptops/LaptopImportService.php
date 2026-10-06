<?php

namespace App\Services\Laptops;

use App\Imports\StudentsSheetImport;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Logika bulk import/update laptop dengan dry run.
 *
 * analyze() = dry run (tidak menulis apa pun), commit() = eksekusi.
 */
class LaptopImportService
{
    public const STATUS_NEW = 'new';
    public const STATUS_UPDATE = 'update';
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_ERROR = 'error';

    public const MAX_ROWS = 2000;

    public const STATUSES = ['available', 'borrowed', 'maintenance', 'retired'];

    /**
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'code' => 'Kode',
        'name' => 'Nama',
        'brand' => 'Brand',
        'model' => 'Model',
        'serial_number' => 'Serial Number',
        'status' => 'Status',
        'owner_student_number' => 'NIS Pemilik',
        'notes' => 'Catatan',
        'spec_cpu' => 'CPU',
        'spec_ram' => 'RAM',
        'spec_storage' => 'Storage',
        'spec_os' => 'OS',
        'qr_code' => 'QR Code',
    ];

    protected const SPEC_FIELDS = ['spec_cpu' => 'cpu', 'spec_ram' => 'ram', 'spec_storage' => 'storage', 'spec_os' => 'os'];

    /**
     * @return array<int, array<string, ?string>>
     */
    public function readRows(string $path, string $disk = 'local'): array
    {
        $sheets = Excel::toArray(new StudentsSheetImport(), $path, $disk);
        $sheet = $sheets[0] ?? [];

        $rows = [];
        foreach ($sheet as $index => $row) {
            $normalized = [];
            foreach (array_keys(self::FIELD_LABELS) as $field) {
                $normalized[$field] = $this->normalizeValue($row[$field] ?? null);
            }

            if (collect($normalized)->filter(fn ($v) => $v !== null)->isEmpty()) {
                continue;
            }

            $rows[$index + 2] = $normalized;
        }

        return $rows;
    }

    /**
     * Dry run. Tidak mengubah database.
     *
     * @param array<int, array<string, ?string>> $rows
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function analyze(array $rows): array
    {
        $codes = $this->column($rows, 'code');
        $serials = $this->column($rows, 'serial_number');
        $qrs = $this->column($rows, 'qr_code');
        $owners = $this->column($rows, 'owner_student_number');

        $candidates = Laptop::query()
            ->with('owner:id,student_number')
            ->where(function ($query) use ($codes, $serials, $qrs) {
                $query->whereIn('code', $codes)
                    ->orWhereIn('serial_number', $serials)
                    ->orWhereIn('qr_code', $qrs);
            })
            ->get();

        $byCode = $candidates->keyBy(fn (Laptop $l) => strtolower($l->code));
        $bySerial = $candidates->whereNotNull('serial_number')->keyBy(fn (Laptop $l) => strtolower($l->serial_number));
        $byQr = $candidates->whereNotNull('qr_code')->keyBy(fn (Laptop $l) => strtolower($l->qr_code));

        $borrowed = BorrowTransaction::query()
            ->whereIn('laptop_id', $candidates->pluck('id'))
            ->whereIn('status', ['borrowed', 'late'])
            ->pluck('laptop_id')
            ->flip();

        $ownerIds = User::students()->whereIn('student_number', $owners)->pluck('id', 'student_number');

        $seen = ['code' => [], 'serial_number' => [], 'qr_code' => []];
        $result = [];

        foreach ($rows as $line => $row) {
            $errors = [];
            $this->validateFormat($row, $errors);

            foreach (['code', 'serial_number', 'qr_code'] as $field) {
                if ($row[$field] === null) {
                    continue;
                }
                $key = strtolower($row[$field]);
                if (isset($seen[$field][$key])) {
                    $errors[] = self::FIELD_LABELS[$field] . " \"{$row[$field]}\" sudah muncul di baris {$seen[$field][$key]}.";
                } else {
                    $seen[$field][$key] = $line;
                }
            }

            if ($row['owner_student_number'] !== null && !$ownerIds->has($row['owner_student_number'])) {
                $errors[] = "Siswa dengan NIS \"{$row['owner_student_number']}\" tidak ditemukan.";
            }

            $matchCode = $row['code'] !== null ? $byCode->get(strtolower($row['code'])) : null;
            $matchSerial = $row['serial_number'] !== null ? $bySerial->get(strtolower($row['serial_number'])) : null;
            $existing = $matchCode ?? $matchSerial;

            if ($matchCode && $matchSerial && $matchCode->id !== $matchSerial->id) {
                $errors[] = "Konflik identitas: kode {$row['code']} milik laptop {$matchCode->name}, tetapi serial {$row['serial_number']} milik laptop {$matchSerial->name} ({$matchSerial->code}).";
            }

            $this->checkOwnedByOther('code', $row['code'], $byCode, $existing, $errors);
            $this->checkOwnedByOther('serial_number', $row['serial_number'], $bySerial, $existing, $errors);
            $this->checkOwnedByOther('qr_code', $row['qr_code'], $byQr, $existing, $errors);

            if ($existing === null && $row['name'] === null) {
                $errors[] = 'Laptop baru wajib mengisi Nama.';
            }

            if ($existing && $row['status'] !== null && $row['status'] !== $existing->status && $borrowed->has($existing->id)) {
                $errors[] = 'Laptop sedang dipinjam, status tidak bisa diubah lewat import. Selesaikan peminjaman terlebih dahulu.';
            }

            $entry = [
                'line' => $line,
                'identifier' => $row['code'] ?? $row['serial_number'] ?? '-',
                'name' => $row['name'] ?? $existing?->name,
                'data' => $row,
                'laptop_id' => $existing?->id,
                'changes' => [],
                'errors' => $errors,
                'status' => self::STATUS_ERROR,
            ];

            if (empty($errors)) {
                if ($existing) {
                    $entry['changes'] = $this->diff($existing, $row);
                    $entry['status'] = empty($entry['changes']) ? self::STATUS_UNCHANGED : self::STATUS_UPDATE;
                } else {
                    $entry['status'] = self::STATUS_NEW;
                }
            }

            $result[$line] = $entry;
        }

        return ['rows' => $result, 'summary' => $this->summarize($result)];
    }

    /**
     * @param array{rows: array<int, array<string, mixed>>, summary: array<string, int>} $plan
     * @return array{created: int, updated: int, unchanged: int, errors: int, aborted: bool}
     */
    public function commit(array $plan, bool $abortOnError = false): array
    {
        $summary = $plan['summary'];

        if ($abortOnError && $summary['error'] > 0) {
            return ['created' => 0, 'updated' => 0, 'unchanged' => $summary['unchanged'], 'errors' => $summary['error'], 'aborted' => true];
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($plan, &$created, &$updated) {
            $rows = collect($plan['rows']);

            $ownerIds = User::students()
                ->whereIn('student_number', $rows->pluck('data.owner_student_number')->filter()->unique()->all())
                ->pluck('id', 'student_number');

            $laptops = Laptop::query()
                ->whereIn('id', $rows->where('status', self::STATUS_UPDATE)->pluck('laptop_id')->all())
                ->get()
                ->keyBy('id');

            // QR yang diisi eksplisit di file tidak boleh "dicuri" oleh QR otomatis laptop lain.
            $reservedQr = $rows->pluck('data.qr_code')->filter()->map(fn ($v) => strtolower($v))->flip();

            foreach ($rows as $entry) {
                if ($entry['status'] === self::STATUS_NEW) {
                    $this->createLaptop($entry['data'], $ownerIds, $reservedQr);
                    $created++;
                } elseif ($entry['status'] === self::STATUS_UPDATE) {
                    $laptop = $laptops->get($entry['laptop_id']);
                    if ($laptop) {
                        $this->updateLaptop($laptop, $entry['changes'], $ownerIds);
                        $updated++;
                    }
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $summary['unchanged'],
            'errors' => $summary['error'],
            'aborted' => false,
        ];
    }

    /**
     * @param array<string, ?string> $data
     */
    protected function createLaptop(array $data, $ownerIds, $reservedQr): Laptop
    {
        $specs = [];
        foreach (self::SPEC_FIELDS as $field => $key) {
            if ($data[$field] !== null) {
                $specs[$key] = $data[$field];
            }
        }

        $qr = $data['qr_code'];
        if ($qr === null) {
            $nis = $data['owner_student_number'];
            $qr = $nis !== null && !$reservedQr->has(strtolower($nis)) && !Laptop::where('qr_code', $nis)->exists()
                ? $nis
                : CodeGenerator::laptopQr();
        }

        return Laptop::create([
            'code' => $data['code'] ?? CodeGenerator::laptopCode(),
            'name' => $data['name'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'serial_number' => $data['serial_number'],
            'status' => $data['status'] ?? 'available',
            'owner_id' => $data['owner_student_number'] !== null ? $ownerIds->get($data['owner_student_number']) : null,
            'specifications' => $specs ?: null,
            'qr_code' => $qr,
            'notes' => $data['notes'],
            'last_checked_at' => now(),
        ]);
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changes
     */
    protected function updateLaptop(Laptop $laptop, array $changes, $ownerIds): void
    {
        $specs = $laptop->specifications ?? [];

        foreach ($changes as $field => [, $new]) {
            if (isset(self::SPEC_FIELDS[$field])) {
                $specs[self::SPEC_FIELDS[$field]] = $new;
            } elseif ($field === 'owner_student_number') {
                $laptop->owner_id = $ownerIds->get($new);
            } else {
                $laptop->{$field} = $new;
            }
        }

        if (array_intersect_key($changes, self::SPEC_FIELDS)) {
            $laptop->specifications = $specs;
        }

        $laptop->save();
    }

    /**
     * @param array<string, ?string> $row
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    protected function diff(Laptop $laptop, array $row): array
    {
        $changes = [];

        foreach (['code', 'name', 'brand', 'model', 'serial_number', 'status', 'notes', 'qr_code'] as $field) {
            if ($row[$field] !== null && (string) $laptop->{$field} !== $row[$field]) {
                $changes[$field] = [$laptop->{$field}, $row[$field]];
            }
        }

        $currentNis = $laptop->owner?->student_number;
        if ($row['owner_student_number'] !== null && $row['owner_student_number'] !== $currentNis) {
            $changes['owner_student_number'] = [$currentNis, $row['owner_student_number']];
        }

        $specs = $laptop->specifications ?? [];
        foreach (self::SPEC_FIELDS as $field => $key) {
            if ($row[$field] !== null && (string) ($specs[$key] ?? '') !== $row[$field]) {
                $changes[$field] = [$specs[$key] ?? null, $row[$field]];
            }
        }

        return $changes;
    }

    /**
     * @param array<string, ?string> $row
     * @param array<int, string> $errors
     */
    protected function validateFormat(array $row, array &$errors): void
    {
        $limits = [
            'code' => 50, 'name' => 255, 'brand' => 100, 'model' => 100, 'serial_number' => 100,
            'owner_student_number' => 50, 'qr_code' => 255,
            'spec_cpu' => 100, 'spec_ram' => 100, 'spec_storage' => 100, 'spec_os' => 100,
        ];

        foreach ($limits as $field => $max) {
            if ($row[$field] !== null && mb_strlen($row[$field]) > $max) {
                $errors[] = self::FIELD_LABELS[$field] . " maksimal {$max} karakter.";
            }
        }

        if ($row['status'] !== null && !in_array($row['status'], self::STATUSES, true)) {
            $errors[] = "Status \"{$row['status']}\" tidak valid (gunakan: " . implode(', ', self::STATUSES) . ').';
        }
    }

    /**
     * @param \Illuminate\Support\Collection<string, Laptop> $index
     * @param array<int, string> $errors
     */
    protected function checkOwnedByOther(string $field, ?string $value, $index, ?Laptop $existing, array &$errors): void
    {
        if ($value === null) {
            return;
        }

        $owner = $index->get(strtolower($value));

        if ($owner && ($existing === null || $owner->id !== $existing->id)) {
            $errors[] = self::FIELD_LABELS[$field] . " \"{$value}\" sudah dipakai laptop {$owner->name} ({$owner->code}).";
        }
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, int>
     */
    protected function summarize(array $entries): array
    {
        $summary = ['total' => count($entries), 'new' => 0, 'update' => 0, 'unchanged' => 0, 'error' => 0];

        foreach ($entries as $entry) {
            $summary[$entry['status']]++;
        }

        return $summary;
    }

    /**
     * @param array<int, array<string, ?string>> $rows
     * @return array<int, string>
     */
    protected function column(array $rows, string $field): array
    {
        return array_values(array_filter(array_column($rows, $field), fn ($v) => $v !== null));
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value)) {
            $value = floor($value) == $value
                ? number_format($value, 0, '', '')
                : rtrim(rtrim(sprintf('%.15F', $value), '0'), '.');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
