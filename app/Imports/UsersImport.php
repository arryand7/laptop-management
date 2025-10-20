<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class UsersImport extends StringValueBinder implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithCustomValueBinder
{
    /**
     * @var array<string, string>
     */
    protected array $passwordCache = [];
    /**
     * @var array<string, bool>
     */
    protected array $existingCardCodes = [];
    /**
     * @var array<string, bool>
     */
    protected array $reservedCardCodes = [];
    /**
     * @var array<string, bool>
     */
    protected array $fileCardCodes = [];
    protected bool $cardCodesLoaded = false;

    public function __construct(protected string $defaultPassword = 'password')
    {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function model(array $row): User
    {
        $row = $this->normalizeRow($row);

        $role = strtolower($row['role'] ?? 'student');

        $cardCode = $role === 'student'
            ? $this->resolveCardCode($row['card_code'] ?? null)
            : null;

        $plainPassword = $row['password'] ?? $this->defaultPassword;

        $data = [
            'name' => $row['name'] ?? null,
            'email' => $row['email'] ?? null,
            'role' => $role,
            'student_number' => $role === 'student' ? ($row['student_number'] ?? null) : null,
            'card_code' => $role === 'student' ? $cardCode : null,
            'classroom' => $role === 'student' ? ($row['classroom'] ?? null) : null,
            'gender' => $role === 'student' ? $this->normalizeGender($row['gender'] ?? null) : null,
            'phone' => $row['phone'] ?? null,
            'password' => $this->hashPassword($plainPassword),
            'qr_code' => $role === 'student' ? $cardCode : null,
            'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];

        return new User($data);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            '*.role' => ['nullable', Rule::in(['admin', 'staff', 'student'])],
            '*.student_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'student_number')],
            '*.card_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'card_code'),
                function ($attribute, $value, $fail) {
                    if (!filled($value)) {
                        return;
                    }
                    $lower = Str::lower((string) $value);
                    if (isset($this->fileCardCodes[$lower])) {
                        $fail("Kode kartu {$value} dipakai lebih dari sekali di file import.");
                        return;
                    }
                    $this->fileCardCodes[$lower] = true;
                },
            ],
            '*.gender' => ['nullable', 'string', 'max:20'],
            '*.classroom' => ['nullable', 'string', 'max:100'],
            '*.phone' => ['nullable', 'string', 'max:50'],
            '*.is_active' => ['nullable'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    protected function hashPassword(string $plain): string
    {
        if ($plain === '') {
            $plain = $this->defaultPassword;
        }

        return $this->passwordCache[$plain] ??= Hash::make($plain);
    }

    protected function resolveCardCode(?string $candidate): string
    {
        $this->ensureCardCodesLoaded();

        $cardCode = trim((string) $candidate);
        if ($cardCode === '') {
            $cardCode = Str::random(64);
        }

        $cardCodeLower = Str::lower($cardCode);
        while ($this->cardCodeExists($cardCodeLower)) {
            $cardCode = Str::random(64);
            $cardCodeLower = Str::lower($cardCode);
        }

        $this->reservedCardCodes[$cardCodeLower] = true;

        return $cardCode;
    }

    protected function cardCodeExists(string $lowerCardCode): bool
    {
        return isset($this->existingCardCodes[$lowerCardCode]) || isset($this->reservedCardCodes[$lowerCardCode]);
    }

    protected function ensureCardCodesLoaded(): void
    {
        if ($this->cardCodesLoaded) {
            return;
        }

        $this->existingCardCodes = array_fill_keys(
            User::whereNotNull('card_code')
                ->pluck('card_code')
                ->map(fn (string $code) => Str::lower($code))
                ->all(),
            true
        );

        $this->cardCodesLoaded = true;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string|null>
     */
    protected function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = $this->normalizeValue($value);
        }

        return $row;
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) == $value) {
                return number_format($value, 0, '', '');
            }

            $formatted = rtrim(rtrim(sprintf('%.15F', $value), '0'), '.');

            return $formatted === '' ? '0' : $formatted;
        }

        return trim((string) $value);
    }

    protected function normalizeGender(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        $maleAliases = ['male', 'laki-laki', 'laki', 'lk', 'l'];
        $femaleAliases = ['female', 'perempuan', 'pr', 'p'];

        if (in_array($normalized, $maleAliases, true)) {
            return 'male';
        }

        if (in_array($normalized, $femaleAliases, true)) {
            return 'female';
        }

        return null;
    }
}
