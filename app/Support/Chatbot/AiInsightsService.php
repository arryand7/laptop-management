<?php

namespace App\Support\Chatbot;

use App\Services\Ai\GeminiClient;
use App\Services\Ai\OpenAiClient;
use App\Support\AppSettingManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class AiInsightsService
{
    public function __construct(
        private OpenAiClient $openAiClient,
        private GeminiClient $geminiClient,
        private InsightsBuilder $builder,
    ) {
    }

    public function respond(string $prompt): array
    {
        $context = $this->builder->build();
        $provider = AppSettingManager::current()->ai_default_provider ?? 'openai';

        $system = <<<'PROMPT'
Anda adalah asisten virtual untuk sistem manajemen peminjaman laptop sekolah menengah.
Gunakan data yang diberikan untuk menjawab pertanyaan secara ringkas, jelas, dan bernada bersahabat.
- Jika pengguna meminta tindakan yang mengubah data, jelaskan bahwa mereka harus menggunakan perintah seperti "pinjam <nis> <kode>" atau "kembalikan <nis>".
- Jika data tidak tersedia, jawab dengan jujur dan beri saran apa yang bisa dicek.
- Sertakan insight tambahan bila relevan (misal tren, perbandingan) selama masih berdasarkan data yang diberikan.
- Jawab dalam Bahasa Indonesia.
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode([
                'prompt' => $prompt,
                'data' => $context,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
        ];

        try {
            if ($provider === 'gemini') {
                $content = $this->geminiClient->chat($messages);
            } elseif ($provider === 'openai') {
                $content = $this->openAiClient->chat($messages);
            } else {
                throw new RuntimeException('Penyedia AI belum didukung.');
            }
        } catch (RuntimeException $e) {
            \Log::warning('AI Insights request failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResponse($context);
        }

        $reply = trim($content);
        if ($reply === '') {
            $reply = 'Maaf, saya belum bisa menemukan jawaban atas pertanyaan tersebut.';
        }

        $suggestions = $this->buildSuggestions($prompt, $context);

        return [
            'reply' => $reply,
            'suggestions' => $suggestions,
        ];
    }

    protected function buildSuggestions(string $prompt, array $context): array
    {
        $base = [
            'pinjam 20231023 LPT-AX45',
            'kembalikan 20231023',
            'Tampilkan status peminjaman hari ini',
        ];

        $promptLower = Str::lower($prompt);
        if (Str::contains($promptLower, ['laporan', 'report'])) {
            array_unshift($base, 'Buat ringkasan laporan bulan ini');
        }

        if (Arr::get($context, 'summary.overdue_count', 0) > 0) {
            array_unshift($base, 'Siapa saja yang terlambat mengembalikan?');
        }

        return array_slice(array_unique($base), 0, 4);
    }

    protected function fallbackResponse(array $context): array
    {
        $summary = $context['summary'] ?? [];
        $lines = [];

        if ($summary) {
            $lines[] = 'Berikut ringkasan terbaru yang dapat saya sampaikan:';
            $lines[] = sprintf('- Total laptop: %s (dipinjam: %s, terlambat: %s)',
                $summary['total_laptops'] ?? 0,
                $summary['borrowed_count'] ?? 0,
                $summary['overdue_count'] ?? 0
            );

            if (!empty($summary['status_breakdown'])) {
                $statusParts = collect($summary['status_breakdown'])
                    ->map(function ($total, $status) {
                        return ucfirst($status) . ': ' . $total;
                    })
                    ->implode(', ');
                $lines[] = '- Status laptop: ' . $statusParts;
            }

            $lines[] = sprintf('- Siswa aktif: %s dari %s',
                $summary['students_active'] ?? 0,
                $summary['students_total'] ?? 0
            );
        }

        $topViolators = collect($context['top_violators'] ?? [])->take(3);
        if ($topViolators->isNotEmpty()) {
            $violatorText = $topViolators->map(function ($item, $index) {
                return ($index + 1) . '. ' . ($item['name'] ?? '-') . ' (' . ($item['nis'] ?? '-') . ') - ' . ($item['count'] ?? 0) . ' pelanggaran';
            })->implode("\n");

            $lines[] = "Top pelanggar:\n" . $violatorText;
        }

        $recentBorrow = collect($context['recent_borrowings'] ?? [])->first();
        if ($recentBorrow) {
            $student = $recentBorrow['student']['name'] ?? '-';
            $laptop = $recentBorrow['laptop']['code'] ?? '-';
            $borrowedAt = $recentBorrow['borrowed_at']
                ? \Illuminate\Support\Carbon::parse($recentBorrow['borrowed_at'])->translatedFormat('d M Y H:i')
                : '-';

            $lines[] = sprintf('Peminjaman terbaru: %s meminjam %s pada %s.', $student, $laptop, $borrowedAt);
        }

        if (empty($lines)) {
            $lines[] = 'Data statistika tidak tersedia saat ini. Silakan gunakan perintah manual seperti "pinjam <nis> <kode>" atau "kembalikan <nis>".';
        }

        return [
            'reply' => implode("\n\n", $lines),
            'suggestions' => $this->buildSuggestions('', $context),
        ];
    }

}
