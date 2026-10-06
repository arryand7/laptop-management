<?php

namespace App\Services\Students;

use App\Imports\StudentsSheetImport;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Logika bulk import/update siswa.
 *
 * analyze() = dry run (tidak menulis apa pun), commit() = eksekusi.
 * Keduanya memakai hasil analyze() yang sama agar preview dan hasil akhir konsisten.
 */
class StudentImportService
{
    public const STATUS_NEW = 'new';
    public const STATUS_UPDATE = 'update';
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_ERROR = 'error';

    public const MAX_ROWS = 2000;

    /**
     * Kolom yang dikenali, beserta label untuk tampilan.
     *
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'name' => 'Nama',
        'email' => 'Email',
        'student_number' => 'NIS',
        'nisn' => 'NISN',
        'classroom' => 'Kelas',
        'gender' => 'Jenis Kelamin',
        'phone' => 'No. HP',
        'is_active' => 'Status Aktif',
        'card_code' => 'Kode Kartu',
        'password' => 'Password',
    ];

    /**
     * Membaca file dan mengembalikan daftar baris (nomor baris Excel => data mentah).
     *
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

            // Lewati baris yang benar-benar kosong.
            if (collect($normalized)->filter(fn ($v) => $v !== null)->isEmpty()) {
                continue;
            }

            // Baris 1 = heading, jadi data pertama ada di baris 2.
            $rows[$index + 2] = $normalized;
        }

        return $rows;
    }

    /**
     * Dry run: cocokkan setiap baris dengan data siswa yang ada. Tidak mengubah database.
     *
     * @param array<int, array<string, ?string>> $rows
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function analyze(array $rows): array
    {
        $nisList = $this->collect($rows, 'student_number');
        $emailList = array_map('strtolower', $this->collect($rows, 'email'));
        $nisnList = $this->collect($rows, 'nisn');
        $cardList = $this->collect($rows, 'card_code');

        $candidates = User::query()
            ->where(function ($query) use ($nisList, $emailList, $nisnList, $cardList) {
                $query->whereIn('student_number', $nisList)
                    ->orWhereIn('email', $emailList)
                    ->orWhereIn('nisn', $nisnList)
                    ->orWhereIn('card_code', $cardList);
            })
            ->get();

        $byNis = $candidates->whereNotNull('student_number')->keyBy('student_number');
        $byEmail = $candidates->keyBy(fn (User $u) => strtolower($u->email));
        $byNisn = $candidates->whereNotNull('nisn')->keyBy('nisn');
        $byCard = $candidates->whereNotNull('card_code')->keyBy('card_code');

        $seen = ['student_number' => [], 'email' => [], 'nisn' => [], 'card_code' => []];
        $result = [];

        foreach ($rows as $line => $row) {
            $errors = [];

            // 1. Validasi format
            $this->validateFormat($row, $errors);

            $email = $row['email'] !== null ? strtolower($row['email']) : null;
            $nis = $row['student_number'];
            $nisn = $row['nisn'];
            $card = $row['card_code'];

            // 2. Duplikat di dalam file
            foreach (['student_number' => $nis, 'email' => $email, 'nisn' => $nisn, 'card_code' => $card] as $field => $value) {
                if ($value === null) {
                    continue;
                }
                $key = strtolower($value);
                if (isset($seen[$field][$key])) {
                    $errors[] = self::FIELD_LABELS[$field] . " \"{$value}\" sudah muncul di baris {$seen[$field][$key]}.";
                } else {
                    $seen[$field][$key] = $line;
                }
            }

            // 3. Cocokkan dengan data yang ada: NIS lebih dulu, lalu email
            $matchNis = $nis !== null ? $byNis->get($nis) : null;
            $matchEmail = $email !== null ? $byEmail->get($email) : null;
            $existing = $matchNis ?? $matchEmail;

            if ($matchNis && $matchEmail && $matchNis->id !== $matchEmail->id) {
                $errors[] = "Konflik identitas: NIS {$nis} milik {$matchNis->name}, tetapi email {$email} milik {$matchEmail->name}.";
            }

            if ($existing && !$existing->isStudent()) {
                $errors[] = "Data cocok dengan akun {$existing->role} ({$existing->email}), bukan siswa.";
            }

            // 4. Data unik tidak boleh dipakai akun lain
            if ($existing === null || $existing->isStudent()) {
                $this->checkOwnedByOther('student_number', $nis, $byNis->get($nis), $existing, $errors);
                $this->checkOwnedByOther('email', $email, $byEmail->get($email), $existing, $errors);
                $this->checkOwnedByOther('nisn', $nisn, $byNisn->get($nisn), $existing, $errors);
                $this->checkOwnedByOther('card_code', $card, $byCard->get($card), $existing, $errors);
            }

            // 5. Siswa baru wajib punya data minimum
            if (!$existing) {
                foreach (['name', 'email', 'student_number'] as $required) {
                    if ($row[$required] === null) {
                        $errors[] = 'Siswa baru wajib mengisi ' . self::FIELD_LABELS[$required] . '.';
                    }
                }
            }

            $entry = [
                'line' => $line,
                'identifier' => $nis ?? $email ?? '-',
                'name' => $row['name'] ?? $existing?->name,
                'data' => $row,
                'user_id' => $existing?->id,
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
     * Eksekusi hasil dry run dalam satu transaksi.
     *
     * @param array{rows: array<int, array<string, mixed>>, summary: array<string, int>} $plan
     * @return array{created: int, updated: int, unchanged: int, errors: int, aborted: bool}
     */
    public function commit(array $plan, ?string $defaultPassword = null, bool $abortOnError = false): array
    {
        $summary = $plan['summary'];

        if ($abortOnError && $summary['error'] > 0) {
            return ['created' => 0, 'updated' => 0, 'unchanged' => $summary['unchanged'], 'errors' => $summary['error'], 'aborted' => true];
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($plan, $defaultPassword, &$created, &$updated) {
            $updateIds = collect($plan['rows'])
                ->where('status', self::STATUS_UPDATE)
                ->pluck('user_id')
                ->all();
            $users = User::query()->whereIn('id', $updateIds)->get()->keyBy('id');

            foreach ($plan['rows'] as $entry) {
                if ($entry['status'] === self::STATUS_NEW) {
                    $this->createStudent($entry['data'], $defaultPassword);
                    $created++;
                } elseif ($entry['status'] === self::STATUS_UPDATE) {
                    $user = $users->get($entry['user_id']);
                    if ($user) {
                        $this->updateStudent($user, $entry['changes']);
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
    protected function createStudent(array $data, ?string $defaultPassword): User
    {
        $cardCode = $data['card_code'] ?? Str::random(64);
        $password = $data['password'] ?? $defaultPassword ?? Str::random(32);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'student_number' => $data['student_number'],
            'nisn' => $data['nisn'],
            'classroom' => $data['classroom'],
            'gender' => $this->normalizeGender($data['gender']),
            'phone' => $data['phone'],
            'password' => Hash::make($password),
            'role' => 'student',
            'card_code' => $cardCode,
            'qr_code' => $cardCode,
            'violations_count' => 0,
            'is_active' => $this->parseBool($data['is_active']) ?? true,
        ]);

        $this->assignDefaultModules($user);

        return $user;
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changes
     */
    protected function updateStudent(User $user, array $changes): void
    {
        foreach ($changes as $field => [, $new]) {
            if ($field === 'password') {
                $user->password = Hash::make((string) $new);
                continue;
            }

            $user->{$field} = $new;

            if ($field === 'card_code') {
                $user->qr_code = $new;
            }
        }

        $user->save();
    }

    /**
     * Bandingkan data file dengan data saat ini. Hanya kolom terisi yang dibandingkan.
     *
     * @param array<string, ?string> $row
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    protected function diff(User $user, array $row): array
    {
        $changes = [];

        foreach (['name', 'student_number', 'nisn', 'classroom', 'phone', 'card_code'] as $field) {
            if ($row[$field] !== null && (string) $user->{$field} !== $row[$field]) {
                $changes[$field] = [$user->{$field}, $row[$field]];
            }
        }

        if ($row['email'] !== null && strtolower($row['email']) !== strtolower((string) $user->email)) {
            $changes['email'] = [$user->email, strtolower($row['email'])];
        }

        $gender = $this->normalizeGender($row['gender']);
        if ($gender !== null && $gender !== $user->gender) {
            $changes['gender'] = [$user->gender, $gender];
        }

        $active = $this->parseBool($row['is_active']);
        if ($active !== null && $active !== (bool) $user->is_active) {
            $changes['is_active'] = [(bool) $user->is_active, $active];
        }

        if ($row['password'] !== null) {
            $changes['password'] = [null, $row['password']];
        }

        return $changes;
    }

    /**
     * @param array<string, ?string> $row
     * @param array<int, string> $errors
     */
    protected function validateFormat(array $row, array &$errors): void
    {
        $limits = ['name' => 255, 'email' => 255, 'student_number' => 50, 'nisn' => 20, 'classroom' => 100, 'phone' => 50, 'card_code' => 255];

        foreach ($limits as $field => $max) {
            if ($row[$field] !== null && mb_strlen($row[$field]) > $max) {
                $errors[] = self::FIELD_LABELS[$field] . " maksimal {$max} karakter.";
            }
        }

        if ($row['email'] !== null && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email \"{$row['email']}\" tidak valid.";
        }

        if ($row['gender'] !== null && $this->normalizeGender($row['gender']) === null) {
            $errors[] = "Jenis kelamin \"{$row['gender']}\" tidak dikenali (gunakan L/P).";
        }

        if ($row['is_active'] !== null && $this->parseBool($row['is_active']) === null) {
            $errors[] = "Status aktif \"{$row['is_active']}\" tidak dikenali (gunakan true/false).";
        }

        if ($row['password'] !== null && mb_strlen($row['password']) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }
    }

    /**
     * @param array<int, string> $errors
     */
    protected function checkOwnedByOther(string $field, ?string $value, ?User $owner, ?User $existing, array &$errors): void
    {
        if ($value === null || $owner === null) {
            return;
        }

        if ($existing === null || $owner->id !== $existing->id) {
            $errors[] = self::FIELD_LABELS[$field] . " \"{$value}\" sudah dipakai oleh {$owner->name}.";
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
    protected function collect(array $rows, string $field): array
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

    protected function normalizeGender(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (Str::lower(trim($value))) {
            'male', 'laki-laki', 'laki', 'lk', 'l', 'm' => 'male',
            'female', 'perempuan', 'pr', 'p', 'f' => 'female',
            default => null,
        };
    }

    protected function parseBool(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return match (Str::lower(trim($value))) {
            '1', 'true', 'yes', 'ya', 'y', 'aktif', 'active' => true,
            '0', 'false', 'no', 'tidak', 'n', 'nonaktif', 'inactive' => false,
            default => null,
        };
    }

    protected function assignDefaultModules(User $user): void
    {
        $defaultKeys = collect(config('modules.list', []))
            ->filter(fn (array $definition) => in_array($user->role, $definition['default_roles'] ?? [], true))
            ->pluck('key');

        if ($defaultKeys->isNotEmpty()) {
            $user->modules()->sync(Module::whereIn('key', $defaultKeys)->pluck('id'));
        }
    }
}
