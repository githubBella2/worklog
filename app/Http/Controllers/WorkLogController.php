<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkLogController extends Controller
{
    /**
     * Display a timeline listing of the work logs grouped by date.
     */
    public function index(Request $request)
    {
        $query = WorkLog::query();

        if ($request->filled('project')) {
            $query->where('project', $request->project);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('logged_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get distinct projects for filter
        $projects = WorkLog::whereNotNull('project')
            ->where('project', '!=', '')
            ->distinct()
            ->pluck('project')
            ->sort()
            ->values();

        // Group logs by logged_at date
        $groupedLogs = $logs->groupBy(function ($log) {
            return $log->logged_at ? $log->logged_at->format('Y-m-d') : 'Tanpa Tanggal';
        });

        return view('logs.index', [
            'groupedLogs' => $groupedLogs,
            'projects' => $projects,
            'filters' => $request->only(['project', 'type', 'status']),
            'totalCount' => $logs->count(),
        ]);
    }

    /**
     * Show the voice recording page.
     */
    public function record()
    {
        return view('logs.record');
    }

    /**
     * Store a newly created work log from audio upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|max:30720', // max 30MB
        ]);

        $file = $request->file('audio');

        // Save audio file locally in public directory
        $extension = $file->getClientOriginalExtension() ?: 'webm';
        $filename = time().'_'.Str::random(8).'.'.$extension;
        $destinationPath = public_path('uploads/audio_logs');

        if (! file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);
        $audioRelativePath = 'uploads/audio_logs/'.$filename;
        $fullPath = public_path($audioRelativePath);

        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($apiKey)) {
            $workLog = WorkLog::create([
                'title' => 'Log Suara ('.date('d M Y H:i').')',
                'audio_path' => $audioRelativePath,
                'transcript' => 'GEMINI_API_KEY tidak dikonfigurasi di .env atau config/services.php. Silakan isi data secara manual.',
                'status' => 'completed',
                'type' => 'other',
            ]);

            return redirect()->route('logs.edit', $workLog->id)
                ->with('error', 'API Key Gemini belum diatur di .env. Audio telah disimpan, silakan melengkapi data secara manual.');
        }

        // Call Gemini API with audio
        try {
            $audioBase64 = base64_encode(file_get_contents($fullPath));
            $mimeType = $file->getClientMimeType() ?: 'audio/webm';

            $promptText = 'Transkripkan audio (bahasa Indonesia/Inggris campur), lalu ekstrak ke JSON dengan field:
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
- logged_at (tanggal format YYYY-MM-DD jika disebut di audio, kalau tidak ada pakai null)
- transcript (transkrip lengkap)

Aturan: JANGAN mengarang. Kalau suatu informasi tidak disebut di audio, isi null atau array kosong. Tulis isi field dalam bahasa yang sama dengan yang diucapkan. Kembalikan HANYA JSON valid.';

            $response = Http::timeout(90)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
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
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->failed()) {
                // Try fallback model if 2.5-flash failed
                $fallbackModel = 'gemini-1.5-flash';
                $response = Http::timeout(90)->post("https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:generateContent?key={$apiKey}", [
                    'contents' => [
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
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);
            }

            if ($response->failed()) {
                Log::error('Gemini API Error: '.$response->body());
                $workLog = WorkLog::create([
                    'title' => 'Log Suara ('.date('d M Y H:i').')',
                    'audio_path' => $audioRelativePath,
                    'transcript' => 'Gagal memanggil API Gemini: '.$response->status().' - '.$response->reason(),
                    'status' => 'completed',
                    'type' => 'other',
                ]);

                return redirect()->route('logs.edit', $workLog->id)
                    ->with('error', 'Gagal memproses audio dengan Gemini API. Audio tersimpan, silakan isi detail secara manual.');
            }

            $responseData = $response->json();
            $rawContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Clean markdown blocks if present
            $cleanedJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawContent));
            $parsed = json_decode($cleanedJson, true);

            if (! is_array($parsed)) {
                // Fallback: Gemini returned invalid JSON
                $workLog = WorkLog::create([
                    'title' => 'Log Suara ('.date('d M Y H:i').')',
                    'audio_path' => $audioRelativePath,
                    'transcript' => $rawContent ?: 'Gemini mengembalikan teks non-JSON.',
                    'status' => 'completed',
                    'type' => 'other',
                ]);

                return redirect()->route('logs.edit', $workLog->id)
                    ->with('warning', 'Gemini mengembalikan format JSON yang tidak valid. Transkrip & audio telah disimpan, silakan lengkapi data.');
            }

            // Valid JSON returned from Gemini
            $validTypes = ['feature', 'bug_fix', 'improvement', 'refactor', 'other'];
            $validStatuses = ['completed', 'in_progress', 'blocked'];

            $type = in_array($parsed['type'] ?? '', $validTypes) ? $parsed['type'] : 'other';
            $status = in_array($parsed['status'] ?? '', $validStatuses) ? $parsed['status'] : 'completed';

            $workLog = WorkLog::create([
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
                'audio_path' => $audioRelativePath,
            ]);

            return redirect()->route('logs.show', $workLog->id)
                ->with('success', 'Voice work log berhasil diproses dan disimpan oleh Gemini AI!');

        } catch (\Exception $e) {
            Log::error('Exception in WorkLogController@store: '.$e->getMessage());

            $workLog = WorkLog::create([
                'title' => 'Log Suara ('.date('d M Y H:i').')',
                'audio_path' => $audioRelativePath,
                'transcript' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
                'status' => 'completed',
                'type' => 'other',
            ]);

            return redirect()->route('logs.edit', $workLog->id)
                ->with('error', 'Terjadi kesalahan saat memproses audio. File audio berhasil disimpan, silakan edit detail manual.');
        }
    }

    /**
     * Display the specified work log detail.
     */
    public function show($id)
    {
        $workLog = WorkLog::findOrFail($id);

        return view('logs.show', compact('workLog'));
    }

    /**
     * Show the form for editing the specified work log.
     */
    public function edit($id)
    {
        $workLog = WorkLog::findOrFail($id);

        return view('logs.edit', compact('workLog'));
    }

    /**
     * Update the specified work log in storage.
     */
    public function update(Request $request, $id)
    {
        $workLog = WorkLog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'project' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
            'type' => 'required|in:feature,bug_fix,improvement,refactor,other',
            'status' => 'required|in:completed,in_progress,blocked',
            'problem' => 'nullable|string',
            'before' => 'nullable|string',
            'actions_raw' => 'nullable|string',
            'after' => 'nullable|string',
            'impact' => 'nullable|string',
            'testing_raw' => 'nullable|string',
            'technologies_raw' => 'nullable|string',
            'next_step' => 'nullable|string',
            'tags_raw' => 'nullable|string',
            'logged_at' => 'required|date',
            'transcript' => 'nullable|string',
        ]);

        // Helper to split text lines or commas into clean arrays
        $parseList = function (?string $raw) {
            if (! $raw) {
                return [];
            }
            $lines = preg_split('/\r\n|\r|\n|,/', $raw);

            return array_values(array_filter(array_map('trim', $lines)));
        };

        $workLog->update([
            'title' => $validated['title'],
            'project' => $validated['project'],
            'module' => $validated['module'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'problem' => $validated['problem'],
            'before' => $validated['before'],
            'actions' => $parseList($request->input('actions_raw')),
            'after' => $validated['after'],
            'impact' => $validated['impact'],
            'testing' => $parseList($request->input('testing_raw')),
            'technologies' => $parseList($request->input('technologies_raw')),
            'next_step' => $validated['next_step'],
            'tags' => $parseList($request->input('tags_raw')),
            'logged_at' => $validated['logged_at'],
            'transcript' => $validated['transcript'],
        ]);

        return redirect()->route('logs.show', $workLog->id)
            ->with('success', 'Work log berhasil diperbarui!');
    }

    /**
     * Remove the specified work log from storage.
     */
    public function destroy($id)
    {
        $workLog = WorkLog::findOrFail($id);

        if ($workLog->audio_path && file_exists(public_path($workLog->audio_path))) {
            @unlink(public_path($workLog->audio_path));
        }

        $workLog->delete();

        return redirect()->route('logs.index')
            ->with('success', 'Work log berhasil dihapus.');
    }

    /**
     * Export 1 week logs into Markdown format.
     */
    public function exportMarkdown(Request $request)
    {
        $startDate = now()->subDays(7)->startOfDay();
        $logs = WorkLog::where('logged_at', '>=', $startDate->toDateString())
            ->orderBy('logged_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // If no logs in last 7 days, get latest 20 logs so export is not empty
        if ($logs->isEmpty()) {
            $logs = WorkLog::orderBy('logged_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        }

        $markdown = "# Voice Work Logs Report\n";
        $markdown .= '*Periode: '.$startDate->format('d M Y').' - '.now()->format('d M Y')."*\n";
        $markdown .= '*Total Log: '.$logs->count()."*\n\n";
        $markdown .= "---\n\n";

        foreach ($logs as $log) {
            $dateStr = $log->logged_at ? $log->logged_at->format('Y-m-d') : '-';
            $projectStr = $log->project ? $log->project.($log->module ? ' / '.$log->module : '') : 'General Project';

            $markdown .= "## {$log->title}\n";
            $markdown .= "- **Project:** {$projectStr}\n";
            $markdown .= "- **Tanggal:** {$dateStr}\n";
            $markdown .= "- **Type:** `{$log->type}` | **Status:** `{$log->status}`\n\n";

            if ($log->problem) {
                $markdown .= "### 📌 Problem\n{$log->problem}\n\n";
            }

            $markdown .= "### ❌ BEFORE\n".($log->before ?: '_Tidak dicantumkan_')."\n\n";

            $markdown .= "### 🛠️ WHAT I DID\n";
            if (! empty($log->actions) && is_array($log->actions)) {
                foreach ($log->actions as $action) {
                    $markdown .= "- {$action}\n";
                }
            } else {
                $markdown .= "_Tidak ada daftar aksi_\n";
            }
            $markdown .= "\n";

            $markdown .= "### ✅ AFTER\n".($log->after ?: '_Tidak dicantumkan_')."\n\n";

            $markdown .= "### 🚀 IMPACT\n".($log->impact ?: '_Tidak dicantumkan_')."\n\n";

            if (! empty($log->testing) && is_array($log->testing)) {
                $markdown .= "### 🧪 TESTING\n";
                foreach ($log->testing as $test) {
                    $markdown .= "- {$test}\n";
                }
                $markdown .= "\n";
            }

            if (! empty($log->technologies) && is_array($log->technologies)) {
                $markdown .= "### 💻 TECHNOLOGIES\n`".implode('`, `', $log->technologies)."`\n\n";
            }

            if ($log->next_step) {
                $markdown .= "### 🎯 NEXT STEP\n{$log->next_step}\n\n";
            }

            $markdown .= "---\n\n";
        }

        $filename = 'worklog_report_'.date('Y-m-d').'.md';

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
