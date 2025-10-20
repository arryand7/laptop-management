<?php

namespace App\Services\Ai;

use App\Support\AppSettingManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiClient
{
    public function chat(array $messages): string
    {
        $setting = AppSettingManager::current();

        $apiKey = config('services.openai.api_key') ?: $setting->openai_api_key;
        if (!$apiKey) {
            throw new RuntimeException('API OpenAI belum dikonfigurasi.');
        }

        $defaultModel = config('services.openai.model', 'gpt-4o-mini');
        $preferredModel = $setting->openai_model ?: $defaultModel;
        $modelsToTry = array_unique([$preferredModel, $defaultModel]);
        $lastError = null;

        foreach ($modelsToTry as $model) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(config('services.openai.timeout', 15))
                ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 600,
                    'temperature' => 0.5,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? '';
            }

            $lastError = Str::limit($response->body(), 200);
        }

        throw new RuntimeException('Gagal menghubungi layanan AI: ' . ($lastError ?? 'respon tidak dikenal'));
    }
}
