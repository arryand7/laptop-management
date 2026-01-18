<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppSettingManager
{
    public static function current(): AppSetting
    {
        $defaults = [
            'site_name' => config('app.name', 'Laptop Management'),
            'timezone' => config('app.timezone', 'UTC'),
            'lending_due_mode' => 'relative',
            'lending_due_days' => 1,
            'lending_due_time' => null,
            'lending_due_date' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'sso_base_url' => config('sso.base_url'),
            'sso_client_id' => config('sso.client_id'),
            'sso_client_secret' => config('sso.client_secret'),
            'sso_redirect_uri' => config('sso.redirect_uri'),
            'sso_scopes' => config('sso.scopes', 'openid profile email roles'),
            'ai_default_provider' => 'openai',
            'openai_model' => config('services.openai.model', 'gpt-4o-mini'),
            'gemini_model' => config('services.gemini.model', 'gemini-1.5-flash'),
            'huggingface_model' => config('services.huggingface.model', 'mistralai/Mistral-7B-Instruct-v0.2'),
            'seb_enabled' => false,
            'seb_config_link' => null,
            'seb_browser_exam_key' => null,
            'seb_exit_key_combination' => null,
            'seb_config_password' => null,
            'seb_client_config_path' => null,
            'seb_additional_notes' => null,
        ];

        if (!Schema::hasTable('app_settings')) {
            return new AppSetting($defaults);
        }

        /** @var AppSetting|null $setting */
        $setting = Cache::rememberForever('app.setting.current', function () {
            return AppSetting::query()->first();
        });

        if (!$setting) {
            $setting = new AppSetting($defaults);
        }

        foreach ($defaults as $key => $value) {
            if ($setting->{$key} === null && $value !== null) {
                $setting->{$key} = $value;
            }
        }

        return $setting;
    }

    public static function refreshCache(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        Cache::forget('app.setting.current');
    }
}
