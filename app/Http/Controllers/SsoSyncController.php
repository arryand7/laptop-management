<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Sso\SsoApiService;
use App\Services\Sso\SsoUserAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SsoSyncController extends Controller
{
    /**
     * Sinkronisasi data pengguna dari API SSO (Gate SSO) ke database lokal.
     * Mendukung mode Full Sync dan Delta Sync.
     *
     * @param Request $request
     * @param SsoApiService $apiService
     * @param SsoUserAdapter $adapter
     * @return JsonResponse
     */
    public function syncAllUsers(Request $request, SsoApiService $apiService, SsoUserAdapter $adapter): JsonResponse
    {
        $mode = $request->input('mode', 'full'); // 'full' atau 'delta'

        try {
            $since = null;
            if ($mode === 'delta') {
                $since = User::whereNotNull('sso_synced_at')->max('sso_synced_at');
            }

            // 1. Fetch data dari SSO via API Service (dengan Token & Delta parameter jika ada)
            $userList = $apiService->fetchUsers($since);

            if (empty($userList)) {
                $message = $mode === 'delta' 
                    ? 'Data pengguna lokal sudah mutakhir (tidak ada perubahan data baru di SSO).'
                    : 'Tidak ada data pengguna yang ditemukan di Server SSO.';

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'synced_count' => 0,
                    'created_count' => 0,
                    'updated_count' => 0,
                    'mode' => $mode,
                ]);
            }

            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;

            // 2. Eksekusi Upsert di dalam Database Transaction untuk menjamin integritas data
            DB::transaction(function () use ($userList, $adapter, &$syncedCount, &$createdCount, &$updatedCount) {
                foreach ($userList as $userData) {
                    if (!is_array($userData)) {
                        continue;
                    }

                    $sub = $userData['sub'] ?? $userData['id_sso'] ?? $userData['id'] ?? null;
                    $email = $userData['email'] ?? null;
                    $nis = $userData['student_number'] ?? $userData['nis'] ?? null;

                    // Cek apakah pengguna sudah tercatat di database lokal
                    $exists = false;
                    if ($sub) {
                        $exists = User::where('sso_sub', $sub)->exists();
                    }
                    if (!$exists && $nis) {
                        $exists = User::where('student_number', $nis)->exists();
                    }
                    if (!$exists && $email) {
                        $exists = User::where('email', $email)->exists();
                    }

                    // Sinkronisasi data via Adapter
                    $adapter->sync($userData);

                    $syncedCount++;
                    if ($exists) {
                        $updatedCount++;
                    } else {
                        $createdCount++;
                    }
                }
            });

            Log::info("SSO Sync ({$mode}) berhasil: {$syncedCount} pengguna diproses ({$createdCount} dibuat, {$updatedCount} diperbarui).");

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyinkronkan {$syncedCount} pengguna dari SSO ({$createdCount} baru, {$updatedCount} diperbarui).",
                'synced_count' => $syncedCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'mode' => $mode,
            ]);

        } catch (\Throwable $e) {
            Log::error('SSO Sync Error: ' . $e->getMessage(), [
                'mode' => $mode,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan data dari SSO: ' . $e->getMessage(),
            ], 500);
        }
    }
}
