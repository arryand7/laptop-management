<?php

namespace App\Imports;

use App\Models\Laptop;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class LaptopsImport extends StringValueBinder implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithCustomValueBinder
{
    /**
     * @var array<string, bool>
     */
    protected array $existingQrCodes = [];
    /**
     * @var array<string, bool>
     */
    protected array $reservedQrCodes = [];
    /**
     * @var array<string, bool>
     */
    protected array $fileQrCodes = [];
    protected bool $qrCodesLoaded = false;

    /**
     * @param array<string, mixed> $row
     */
    public function model(array $row): Laptop
    {
        $row = $this->normalizeRow($row);

        $ownerIdentifier = $row['owner_student_number'] ?? null;
        $ownerId = null;

        if ($ownerIdentifier) {
            $ownerId = User::students()->where('student_number', $ownerIdentifier)->value('id');
        }

        $specs = array_filter([
            'cpu' => $row['spec_cpu'] ?? null,
            'ram' => $row['spec_ram'] ?? null,
            'storage' => $row['spec_storage'] ?? null,
            'os' => $row['spec_os'] ?? null,
        ], fn ($value) => $value !== null);

        $qrCode = $this->resolveQrCode($row['qr_code'] ?? null, $ownerIdentifier);

        return new Laptop([
            'code' => $row['code'] ?? CodeGenerator::laptopCode(),
            'name' => $row['name'] ?? null,
            'brand' => $row['brand'] ?? null,
            'model' => $row['model'] ?? null,
            'serial_number' => $row['serial_number'] ?? null,
            'status' => $row['status'] ?? 'available',
            'owner_id' => $ownerId,
            'specifications' => $specs ?: null,
            'qr_code' => $qrCode,
            'notes' => $row['notes'] ?? null,
            'last_checked_at' => now(),
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.code' => ['nullable', 'string', 'max:50', Rule::unique('laptops', 'code')],
            '*.serial_number' => ['nullable', 'string', 'max:100', Rule::unique('laptops', 'serial_number')],
            '*.qr_code' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!filled($value)) {
                        return;
                    }

                    $lower = Str::lower(trim((string) $value));
                    if (isset($this->fileQrCodes[$lower])) {
                        $fail("QR code {$value} dipakai lebih dari sekali di file import.");
                    } else {
                        $this->fileQrCodes[$lower] = true;
                    }
                },
            ],
            '*.status' => ['nullable', Rule::in(['available', 'borrowed', 'maintenance', 'retired'])],
            '*.owner_student_number' => ['nullable', 'string', 'exists:users,student_number'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
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

    protected function resolveQrCode(?string $candidate, ?string $fallbackOwnerNumber): string
    {
        $this->ensureQrCodesLoaded();

        $qrCode = trim((string) ($candidate ?? ''));

        if ($qrCode === '' && $fallbackOwnerNumber) {
            $qrCode = trim($fallbackOwnerNumber);
        }

        if ($qrCode === '') {
            $qrCode = CodeGenerator::laptopQr();
        }

        $lower = Str::lower($qrCode);
        while ($this->qrCodeExists($lower)) {
            $qrCode = CodeGenerator::laptopQr();
            $lower = Str::lower($qrCode);
        }

        $this->reservedQrCodes[$lower] = true;
        $this->fileQrCodes[$lower] = true;

        return $qrCode;
    }

    protected function ensureQrCodesLoaded(): void
    {
        if ($this->qrCodesLoaded) {
            return;
        }

        $this->existingQrCodes = array_fill_keys(
            Laptop::whereNotNull('qr_code')
                ->pluck('qr_code')
                ->filter()
                ->map(fn ($code) => Str::lower($code))
                ->all(),
            true
        );

        $this->qrCodesLoaded = true;
    }

    protected function qrCodeExists(string $lower): bool
    {
        return isset($this->existingQrCodes[$lower])
            || isset($this->reservedQrCodes[$lower])
            || isset($this->fileQrCodes[$lower]);
    }
}
