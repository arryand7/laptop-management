<?php

namespace App\Services\Sso;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SsoUserAdapter
{
    /**
     * Menerima payload mentah dari SSO dan mengembalikan array data
     * yang 100% kompatibel dengan skema tabel users lokal.
     *
     * @param array<string, mixed> $ssoPayload
     * @param User|null $existingUser
     * @return array<string, mixed>
     */
    public function transform(array $ssoPayload, ?User $existingUser = null): array
    {
        $role = $this->translateRole($ssoPayload['role'] ?? $ssoPayload['roles'] ?? $ssoPayload['role_id'] ?? null);
        $name = $this->resolveFullName($ssoPayload);
        $email = strtolower(trim((string) ($ssoPayload['email'] ?? '')));
        $gender = $this->normalizeGender($ssoPayload['gender'] ?? null);
        $phone = $this->cleanPhoneNumber($ssoPayload['phone'] ?? $ssoPayload['phone_number'] ?? null);
        $isActive = filter_var($ssoPayload['is_active'] ?? $ssoPayload['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $studentNumber = $role === 'student' ? ($ssoPayload['student_number'] ?? $ssoPayload['nis'] ?? null) : null;
        $classroom = $role === 'student' ? ($ssoPayload['classroom'] ?? $ssoPayload['class_name'] ?? null) : null;

        // Card code resolution: pertahankan yang sudah ada, gunakan dari SSO, atau generate untuk student baru
        $cardCode = null;
        if ($role === 'student') {
            $cardCode = $ssoPayload['card_code']
                ?? $ssoPayload['rfid']
                ?? $existingUser?->card_code
                ?? Str::random(64);
        }

        // Proses foto profil (Mendukung URL atau Base64)
        $avatarRaw = $ssoPayload['avatar_url']
            ?? $ssoPayload['avatar']
            ?? $ssoPayload['photo_url']
            ?? $ssoPayload['photo']
            ?? $ssoPayload['picture']
            ?? null;

        $avatarPath = $this->processAvatar($avatarRaw, $existingUser?->avatar_path);

        $transformed = [
            'sso_sub' => (string) ($ssoPayload['sub'] ?? $ssoPayload['id_sso'] ?? $ssoPayload['id'] ?? null),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'gender' => $gender,
            'student_number' => $studentNumber,
            'classroom' => $classroom,
            'phone' => $phone,
            'avatar_path' => $avatarPath,
            'is_active' => $isActive,
            'sso_synced_at' => Carbon::now(),
        ];

        // Sinkronisasi card_code dan qr_code jika role student
        if ($role === 'student' && $cardCode) {
            $transformed['card_code'] = $cardCode;
            $transformed['qr_code'] = $cardCode;
        }

        return $transformed;
    }

    /**
     * Melakukan Upsert (Insert atau Update) secara Idempotent
     * tanpa mengubah Primary Key (id) lokal atau merusak relasi.
     *
     * @param array<string, mixed> $ssoPayload
     * @return User
     */
    public function sync(array $ssoPayload): User
    {
        $sub = $ssoPayload['sub'] ?? $ssoPayload['id_sso'] ?? $ssoPayload['id'] ?? null;
        $email = strtolower(trim((string) ($ssoPayload['email'] ?? '')));
        $nis = $ssoPayload['student_number'] ?? $ssoPayload['nis'] ?? null;

        // 1. Deterministic Identity Resolution (Cari user lokal)
        $user = null;

        if ($sub) {
            $user = User::where('sso_sub', $sub)->first();
        }

        if (!$user && $nis) {
            $user = User::where('student_number', $nis)->first();
        }

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        // 2. Transformasi data SSO ke struktur lokal
        $transformedData = $this->transform($ssoPayload, $user);

        if ($user) {
            // Mode UPDATE: Perbarui data tanpa mengubah Primary Key
            $user->fill($transformedData);
            $user->save();
        } else {
            // Mode INSERT: Buat user baru dengan default password ter-hash
            $transformedData['password'] = Hash::make(Str::random(32));
            $user = User::create($transformedData);

            // Assign default module permissions sesuai role
            $this->assignDefaultModules($user);
        }

        return $user;
    }

    /**
     * Memproses avatar baik berupa URL Web maupun string Base64.
     * Gambar disimpan ke storage lokal public (storage/app/public/avatars/).
     *
     * @param string|null $avatarInput
     * @param string|null $fallbackPath
     * @return string|null
     */
    public function processAvatar(?string $avatarInput, ?string $fallbackPath = null): ?string
    {
        if (empty($avatarInput) || !is_string($avatarInput)) {
            return $fallbackPath;
        }

        $avatarInput = trim($avatarInput);

        // 1. Kasus Base64 Data URI atau raw Base64 string
        if (str_starts_with($avatarInput, 'data:image/') || preg_match('/^[a-zA-Z0-9\/\r\n+={}]*$/', $avatarInput) && strlen($avatarInput) > 100) {
            return $this->saveBase64Avatar($avatarInput, $fallbackPath);
        }

        // 2. Kasus URL Web (HTTP / HTTPS)
        if (filter_var($avatarInput, FILTER_VALIDATE_URL)) {
            return $this->downloadAvatarUrl($avatarInput, $fallbackPath);
        }

        return $fallbackPath;
    }

    /**
     * Download avatar dari URL SSO dan simpan ke local public storage.
     *
     * @param string $avatarUrl
     * @param string|null $fallbackPath
     * @return string|null
     */
    public function downloadAvatarUrl(string $avatarUrl, ?string $fallbackPath = null): ?string
    {
        try {
            $response = Http::timeout(5)->get($avatarUrl);

            if ($response->successful() && !empty($response->body())) {
                $contentType = $response->header('Content-Type');
                $extension = match ($contentType) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    default => 'jpg',
                };

                $filename = 'sso_' . Str::random(24) . '.' . $extension;
                $relativePath = 'avatars/' . $filename;

                Storage::disk('public')->put($relativePath, $response->body());

                // Hapus avatar lama jika berbeda dan tersimpan di storage lokal
                $this->cleanupOldAvatar($fallbackPath, $relativePath);

                return $relativePath;
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal mengunduh avatar SSO dari URL [{$avatarUrl}]: " . $e->getMessage());
        }

        return $fallbackPath;
    }

    /**
     * Decode dan simpan Base64 avatar ke storage lokal.
     *
     * @param string $base64String
     * @param string|null $fallbackPath
     * @return string|null
     */
    public function saveBase64Avatar(string $base64String, ?string $fallbackPath = null): ?string
    {
        try {
            $extension = 'jpg';

            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $extension = strtolower($matches[1]);
                $base64Data = substr($base64String, strpos($base64String, ',') + 1);
            } else {
                $base64Data = $base64String;
            }

            $decoded = base64_decode($base64Data, true);

            if ($decoded !== false && !empty($decoded)) {
                $filename = 'sso_' . Str::random(24) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
                $relativePath = 'avatars/' . $filename;

                Storage::disk('public')->put($relativePath, $decoded);

                $this->cleanupOldAvatar($fallbackPath, $relativePath);

                return $relativePath;
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal menyimpan Base64 avatar SSO: " . $e->getMessage());
        }

        return $fallbackPath;
    }

    /**
     * Menghapus file avatar lama jika berbeda dengan file baru.
     */
    protected function cleanupOldAvatar(?string $oldPath, string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    /**
     * Menggabungkan nama lengkap secara cerdas.
     */
    protected function resolveFullName(array $sso): string
    {
        if (!empty($sso['name'])) {
            return trim((string) $sso['name']);
        }

        $firstName = trim((string) ($sso['first_name'] ?? $sso['given_name'] ?? ''));
        $lastName = trim((string) ($sso['last_name'] ?? $sso['family_name'] ?? ''));

        $fullName = trim("{$firstName} {$lastName}");

        return $fullName !== '' ? $fullName : 'User SSO';
    }

    /**
     * Menerjemahkan berbagai format role SSO ke ENUM role lokal: ('admin', 'staff', 'student').
     */
    protected function translateRole(mixed $rawRole): string
    {
        if (is_array($rawRole)) {
            $rawRole = $rawRole[0] ?? 'student';
        }

        $role = Str::lower(trim((string) $rawRole));

        return match ($role) {
            '1', 'admin', 'administrator', 'superadmin' => 'admin',
            '2', 'staff', 'teacher', 'guru', 'petugas', 'operator' => 'staff',
            '3', 'student', 'siswa', 'santri', 'peserta_didik' => 'student',
            default => 'student',
        };
    }

    /**
     * Normalisasi nilai gender ke ENUM ('male', 'female') atau null.
     */
    protected function normalizeGender(mixed $gender): ?string
    {
        if ($gender === null) {
            return null;
        }

        $val = Str::lower(trim((string) $gender));

        $maleAliases = ['male', 'm', 'laki-laki', 'laki', 'l', '1'];
        $femaleAliases = ['female', 'f', 'perempuan', 'p', 'pr', '2'];

        if (in_array($val, $maleAliases, true)) {
            return 'male';
        }

        if (in_array($val, $femaleAliases, true)) {
            return 'female';
        }

        return null;
    }

    /**
     * Membersihkan format nomor telepon.
     */
    protected function cleanPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Memberikan module default jika user baru dibuat.
     */
    protected function assignDefaultModules(User $user): void
    {
        $defaultKeys = collect(config('modules.list', []))
            ->filter(fn (array $definition) => in_array($user->role, $definition['default_roles'] ?? [], true))
            ->pluck('key');

        if ($defaultKeys->isNotEmpty()) {
            $moduleIds = Module::whereIn('key', $defaultKeys)->pluck('id');
            $user->modules()->sync($moduleIds);
        }
    }
}
