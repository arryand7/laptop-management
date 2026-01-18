<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AppSettingManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function redirect(Request $request)
    {
        $config = $this->config();

        if (!$config['client_id'] || !$config['client_secret'] || !$config['redirect_uri']) {
            return redirect()->route('login')->withErrors([
                'email' => 'SSO belum dikonfigurasi. Silakan hubungi admin.',
            ]);
        }

        $state = Str::random(40);
        $request->session()->put('sso_state', $state);
        $request->session()->put('sso_intended', $request->input('intended'));

        $authorizeUrl = $config['base_url'].'/oauth/authorize?'.http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scopes'],
            'state' => $state,
        ]);

        return redirect()->away($authorizeUrl);
    }

    public function callback(Request $request)
    {
        if (!$this->validState($request)) {
            return $this->fail();
        }

        $code = $request->input('code');
        if (!$code) {
            return $this->fail('Kode otorisasi tidak ditemukan.');
        }

        $config = $this->config();
        $tokenResponse = Http::asForm()->post($config['base_url'].'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'code' => $code,
        ]);

        if (!$tokenResponse->successful()) {
            return $this->fail('Gagal menukar token SSO.');
        }

        $accessToken = $tokenResponse->json('access_token');
        if (!$accessToken) {
            return $this->fail('Access token tidak ditemukan.');
        }

        $userInfoResponse = Http::withToken($accessToken)->get($config['base_url'].'/oauth/userinfo');
        if (!$userInfoResponse->successful()) {
            return $this->fail('Gagal mengambil profil SSO.');
        }

        $claims = $userInfoResponse->json();
        $sub = $claims['sub'] ?? null;
        $email = $claims['email'] ?? null;

        if (!$sub) {
            return $this->fail('SSO tidak mengembalikan data pengguna yang valid.');
        }

        $user = User::where('sso_sub', $sub)->first();
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            return $this->fail('Akun Anda belum terdaftar di aplikasi ini.');
        }

        if (!$user->is_active) {
            return $this->fail('Akun Anda sedang dinonaktifkan.');
        }

        if (!$user->sso_sub) {
            $user->forceFill([
                'sso_sub' => $sub,
                'sso_synced_at' => now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('sso_intended');
        if ($intended && filter_var($intended, FILTER_VALIDATE_URL)) {
            return redirect($intended);
        }

        return redirect()->intended(route('dashboard'));
    }

    protected function config(): array
    {
        $setting = AppSettingManager::current();
        $baseUrl = $setting->sso_base_url ?: config('sso.base_url');
        $scopes = $setting->sso_scopes ?: config('sso.scopes', 'openid profile email roles');

        return [
            'base_url' => $baseUrl ? rtrim($baseUrl, '/') : null,
            'client_id' => $setting->sso_client_id ?: config('sso.client_id'),
            'client_secret' => $setting->sso_client_secret ?: config('sso.client_secret'),
            'redirect_uri' => $setting->sso_redirect_uri ?: config('sso.redirect_uri'),
            'scopes' => $scopes,
        ];
    }

    protected function validState(Request $request): bool
    {
        $state = (string) $request->input('state');
        $expected = (string) $request->session()->pull('sso_state');

        return $state !== '' && $expected !== '' && hash_equals($expected, $state);
    }

    protected function fail(string $message = 'SSO login gagal. Silakan coba lagi.'): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }
}
