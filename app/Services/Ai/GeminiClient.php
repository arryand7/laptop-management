<?php

namespace App\Services\Ai;

use App\Support\AppSettingManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiClient
{
    public function chat(array $messages): string
    {
        $setting = AppSettingManager::current();

        $apiKey = $setting->gemini_api_key;
        if (!$apiKey) {
            throw new RuntimeException('API Gemini belum dikonfigurasi.');
        }

        $model = $setting->gemini_model ?: config('services.gemini.model', 'gemini-1.5-flash');
        $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');

        $systemParts = [];
        $contents = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if ($role === 'system') {
                $systemParts[] = ['text' => $content];
                continue;
            }

            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $content],
                ],
            ];
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => '']],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 600,
            ],
        ];

        if ($systemParts) {
            $payload['systemInstruction'] = [
                'parts' => $systemParts,
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->withOptions(['query' => ['key' => $apiKey]])
            ->timeout(config('services.gemini.timeout', 15))
            ->post($baseUrl . '/v1beta/models/' . $model . ':generateContent', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gagal menghubungi layanan Gemini: ' . Str::limit($response->body(), 200));
        }

        $data = $response->json();
        $parts = \Illuminate\Support\Arr::get($data, 'candidates.0.content.parts', []);
        $text = collect($parts)->map(fn ($part) => $part['text'] ?? '')->filter()->implode("\n");

        if ($text === '') {
            throw new RuntimeException('Jawaban Gemini kosong.');
        }

        return $text;
    }
}
