<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkLogAiService
{
    /**
     * Common prompt instructions for JSON extraction.
     */
    protected function getSystemPromptInstruction(): string
    {
        return 'Ekstrak ke JSON dengan field:
- title (judul singkat)
- project
- module
- type (enum: feature, bug_fix, improvement, refactor, other)
- problem
- before (kondisi sebelum perubahan)
- actions (array string: apa saja yang dikerjakan)
- after (kondisi setelah perubahan)
- impact (dampak ke user/proses/bisnis)
- testing (array string)
- technologies (array string)
- status (enum: completed, in_progress, blocked)
- next_step
- tags (array)
- logged_at (tanggal format YYYY-MM-DD jika disebut, kalau tidak ada pakai null)
- transcript (transkrip lengkap / teks asli)

Aturan: JANGAN mengarang. Kalau suatu informasi tidak disebut, isi null atau array kosong. Tulis isi field dalam bahasa yang sama dengan input. Kembalikan HANYA JSON valid.';
    }

    /**
     * Extract work log data from audio file using Gemini API.
     */
    public function extractFromAudio(string $audioFullPath, string $mimeType): array
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key Gemini belum diatur di .env. Audio telah disimpan, silakan melengkapi data secara manual.',
                'data' => [
                    'title' => 'Log Suara ('.date('d M Y H:i').')',
                    'transcript' => 'GEMINI_API_KEY tidak dikonfigurasi di .env atau config/services.php. Silakan isi data secara manual.',
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }

        try {
            $audioBase64 = base64_encode(file_get_contents($audioFullPath));
            $promptText = 'Transkripkan audio (bahasa Indonesia/Inggris campur), lalu '.$this->getSystemPromptInstruction();

            $contents = [
                [
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $audioBase64,
                            ],
                        ],
                        [
                            'text' => $promptText,
                        ],
                    ],
                ],
            ];

            return $this->callGeminiAndParse($contents, $apiKey, $model, 'Log Suara');
        } catch (\Exception $e) {
            Log::error('Exception in WorkLogAiService@extractFromAudio: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan saat memproses audio: '.$e->getMessage(),
                'data' => [
                    'title' => 'Log Suara ('.date('d M Y H:i').')',
                    'transcript' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }
    }

    /**
     * Extract work log data from text content using Gemini API.
     */
    public function extractFromText(string $text): array
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key Gemini belum diatur di .env. Silakan melengkapi data secara manual.',
                'data' => [
                    'title' => 'Log Teks ('.date('d M Y H:i').')',
                    'transcript' => $text,
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }

        try {
            $promptText = 'Analisis dan ekstrak teks berikut ke JSON. Untuk field transcript, pastikan nilainya persis sama dengan teks input.'."\n\n".
                $this->getSystemPromptInstruction()."\n\n".
                "Teks Input:\n---\n".$text."\n---";

            $contents = [
                [
                    'parts' => [
                        [
                            'text' => $promptText,
                        ],
                    ],
                ],
            ];

            $result = $this->callGeminiAndParse($contents, $apiKey, $model, 'Log Teks');

            if (isset($result['data'])) {
                $result['data']['transcript'] = $text;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception in WorkLogAiService@extractFromText: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan saat memproses teks: '.$e->getMessage(),
                'data' => [
                    'title' => 'Log Teks ('.date('d M Y H:i').')',
                    'transcript' => $text,
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }
    }

    /**
     * Helper to call Gemini API, fallback if needed, and parse response JSON.
     */
    protected function callGeminiAndParse(array $contents, string $apiKey, string $model, string $defaultTitlePrefix): array
    {
        $response = Http::timeout(90)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => $contents,
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            // Fallback model if primary model failed
            $fallbackModel = 'gemini-1.5-flash';
            $response = Http::timeout(90)->post("https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:generateContent?key={$apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);
        }

        if ($response->failed()) {
            Log::error('Gemini API Error: '.$response->body());

            return [
                'success' => false,
                'error' => 'Gagal memanggil API Gemini: '.$response->status().' - '.$response->reason(),
                'data' => [
                    'title' => $defaultTitlePrefix.' ('.date('d M Y H:i').')',
                    'transcript' => 'Gagal memanggil API Gemini: '.$response->status().' - '.$response->reason(),
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }

        $responseData = $response->json();
        $rawContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $cleanedJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawContent));
        $parsed = json_decode($cleanedJson, true);

        if (! is_array($parsed)) {
            Log::warning('Gemini returned non-JSON content: '.$rawContent);

            return [
                'success' => false,
                'error' => 'Gemini mengembalikan format JSON yang tidak valid.',
                'data' => [
                    'title' => $defaultTitlePrefix.' ('.date('d M Y H:i').')',
                    'transcript' => $rawContent ?: 'Gemini mengembalikan teks non-JSON.',
                    'status' => 'completed',
                    'type' => 'other',
                ],
            ];
        }

        $validTypes = ['feature', 'bug_fix', 'improvement', 'refactor', 'other'];
        $validStatuses = ['completed', 'in_progress', 'blocked'];

        $type = in_array($parsed['type'] ?? '', $validTypes) ? $parsed['type'] : 'other';
        $status = in_array($parsed['status'] ?? '', $validStatuses) ? $parsed['status'] : 'completed';

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'title' => $parsed['title'] ?? 'Log Work Note ('.date('d M Y').')',
                'project' => $parsed['project'] ?? null,
                'module' => $parsed['module'] ?? null,
                'type' => $type,
                'status' => $status,
                'problem' => $parsed['problem'] ?? null,
                'before' => $parsed['before'] ?? null,
                'actions' => is_array($parsed['actions'] ?? null) ? $parsed['actions'] : [],
                'after' => $parsed['after'] ?? null,
                'impact' => $parsed['impact'] ?? null,
                'testing' => is_array($parsed['testing'] ?? null) ? $parsed['testing'] : [],
                'technologies' => is_array($parsed['technologies'] ?? null) ? $parsed['technologies'] : [],
                'next_step' => $parsed['next_step'] ?? null,
                'tags' => is_array($parsed['tags'] ?? null) ? $parsed['tags'] : [],
                'logged_at' => ! empty($parsed['logged_at']) ? $parsed['logged_at'] : now()->toDateString(),
                'transcript' => $parsed['transcript'] ?? null,
            ],
        ];
    }
}
