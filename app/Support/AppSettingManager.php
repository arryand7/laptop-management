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
            'lending_due_mode' => 'relative',
            'lending_due_days' => 1,
            'lending_due_time' => null,
            'lending_due_date' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'ai_default_provider' => 'openai',
            'openai_model' => config('services.openai.model', 'gpt-4o-mini'),
            'gemini_model' => config('services.gemini.model', 'gemini-1.5-flash'),
            'huggingface_model' => config('services.huggingface.model', 'mistralai/Mistral-7B-Instruct-v0.2'),
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
