<?php

namespace App\Services\Sso;

use App\Support\AppSettingManager;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoApiService
{
    /**
     * Mengambil daftar user dari Gate SSO API.
     * Mendukung Delta Sync (hanya user yang diperbarui sejak tanggal tertentu).
     *
     * @param CarbonInterface|string|null $since Waktu sinkronisasi terakhir untuk Delta Sync
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     */
    public function fetchUsers(CarbonInterface|string|null $since = null): array
    {
        $config = $this->getConfig();

        if (empty($config['base_url'])) {
            throw new \RuntimeException('SSO Base URL belum dikonfigurasi pada pengaturan aplikasi.');
        }

        $baseUrl = rtrim($config['base_url'], '/');
        $accessToken = $this->getAccessToken($config);

        $queryParams = [];
        if ($since) {
            $formattedSince = $since instanceof CarbonInterface ? $since->toIso8601String() : $since;
            $queryParams['since'] = $formattedSince;
            $queryParams['updated_since'] = $formattedSince;
        }

        // Endpoint prioritas dari Gate SSO
        $endpoints = [
            $baseUrl . '/api/sso/users',
            $baseUrl . '/api/users',
            $baseUrl . '/oauth/users',
        ];

        $client = Http::timeout(15)->acceptJson();

        if ($accessToken) {
            $client = $client->withToken($accessToken);
        } elseif (!empty($config['client_secret'])) {
            $client = $client->withHeaders([
                'X-Client-Id' => $config['client_id'] ?? '',
                'X-Client-Secret' => $config['client_secret'],
            ]);
        }

        $lastException = null;

        foreach ($endpoints as $endpoint) {
            try {
                $response = $client->get($endpoint, $queryParams);

                if ($response->successful()) {
                    return $this->extractUserList($response);
                }

                if ($response->status() === 404) {
                    // Coba endpoint alternatif berikutnya
                    continue;
                }

                $errorMessage = $response->json('message') ?? "HTTP {$response->status()}: {$response->body()}";
                Log::warning("SSO API Endpoint {$endpoint} mengembalikan error: {$errorMessage}");
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("Koneksi gagal ke {$endpoint}: " . $e->getMessage());
            }
        }

        if ($lastException) {
            throw new \RuntimeException('Gagal terhubung ke Server SSO: ' . $lastException->getMessage(), 0, $lastException);
        }

        throw new \RuntimeException('Tidak dapat mengambil data pengguna dari API SSO (Endpoint tidak merespons valid).');
    }

    /**
     * Mengambil Access Token menggunakan Client Credentials Grant.
     * Token di-cache sesuai dengan expires_in untuk efisiensi request.
     *
     * @param array<string, mixed> $config
     * @return string|null
     */
    public function getAccessToken(array $config): ?string
    {
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['base_url'])) {
            return null;
        }

        $cacheKey = 'sso_client_credentials_token_' . md5($config['client_id']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($config) {
            try {
                $tokenUrl = rtrim($config['base_url'], '/') . '/oauth/token';

                $response = Http::asForm()->timeout(10)->post($tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'scope' => $config['scopes'] ?? 'openid profile email roles',
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::warning('Gagal mendapatkan Client Credentials Access Token dari SSO: ' . $response->body());
            } catch (\Throwable $e) {
                Log::warning('Exception saat request token SSO: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Ekstrak daftar array user dari response JSON.
     *
     * @param Response $response
     * @return array<int, array<string, mixed>>
     */
    protected function extractUserList(Response $response): array
    {
        $json = $response->json();

        if (isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }

        if (isset($json['users']) && is_array($json['users'])) {
            return $json['users'];
        }

        if (is_array($json) && !empty($json) && isset($json[0]) && is_array($json[0])) {
            return $json;
        }

        return [];
    }

    /**
     * Ambil konfigurasi SSO saat ini.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $setting = AppSettingManager::current();
        $baseUrl = $setting->sso_base_url ?: config('sso.base_url');

        return [
            'base_url' => $baseUrl ? rtrim($baseUrl, '/') : null,
            'client_id' => $setting->sso_client_id ?: config('sso.client_id'),
            'client_secret' => $setting->sso_client_secret ?: config('sso.client_secret'),
            'redirect_uri' => $setting->sso_redirect_uri ?: config('sso.redirect_uri'),
            'scopes' => $setting->sso_scopes ?: config('sso.scopes', 'openid profile email roles'),
        ];
    }
}
