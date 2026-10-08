<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Services\WorkLogAiService;
use App\Services\WorkLogImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkLogController extends Controller
{
    public function __construct(
        protected WorkLogAiService $aiService,
        protected WorkLogImageService $images
    ) {}

    /**
     * Display a timeline listing of the work logs grouped by date.
     */
    public function index(Request $request)
    {
                $query = WorkLog::query()->withCount(['beforeImages', 'afterImages'])->with('images');
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
        $request->validate(array_merge([
            'audio' => 'required|file|max:30720', // max 30MB
        ], WorkLogImageService::rules()), WorkLogImageService::messages());

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
        $mimeType = $file->getClientMimeType() ?: 'audio/webm';

        $result = $this->aiService->extractFromAudio($fullPath, $mimeType);

        $workLogData = array_merge($result['data'], [
            'audio_path' => $audioRelativePath,
            'source' => 'voice',
        ]);

        $workLog = WorkLog::create($workLogData);
        $this->images->storeFromRequest($request, $workLog);

        if (! $result['success']) {
            return redirect()->route('logs.edit', $workLog)
                ->with('error', $result['error'] ?? 'Gagal memproses audio dengan Gemini API. Detail tersimpan, silakan lengkapi manual.');
        }

        return redirect()->route('logs.show', $workLog)
            ->with('success', 'Voice work log berhasil diproses dan disimpan oleh Gemini AI!');
    }

    /**
     * Store a newly created work log from text input.
     */
    public function storeText(Request $request)
    {
        $validated = $request->validate(array_merge([
            'content' => 'required|string|min:20|max:5000',
        ], WorkLogImageService::rules()), WorkLogImageService::messages());

        $result = $this->aiService->extractFromText($validated['content']);

        $workLogData = array_merge($result['data'], [
            'audio_path' => null,
            'source' => 'text',
        ]);

        $workLog = WorkLog::create($workLogData);
        $this->images->storeFromRequest($request, $workLog);

        if (! $result['success']) {
            return redirect()->route('logs.edit', $workLog)
                ->with('warning', $result['error'] ?? 'Gemini mengembalikan format yang tidak valid. Teks telah disimpan, silakan lengkapi data.');
        }

        return redirect()->route('logs.show', $workLog)
            ->with('success', 'Text work log berhasil diproses dan disimpan oleh Gemini AI!');
    }

    /**
     * Display the specified work log detail.
     */
    public function show(WorkLog $workLog)
    {
        $workLog->load(['beforeImages', 'afterImages']);

        return view('logs.show', compact('workLog'));
    }

    /**
     * Show the form for editing the specified work log.
     */
    public function edit(WorkLog $workLog)
    {
        $workLog->load(['beforeImages', 'afterImages']);

        return view('logs.edit', compact('workLog'));
    }

    /**
     * Update the specified work log in storage.
     */
    public function update(Request $request, WorkLog $workLog)
    {
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

        return redirect()->route('logs.show', $workLog)
            ->with('success', 'Work log berhasil diperbarui!');
    }

    /**
     * Remove the specified work log from storage.
     */
    public function destroy(WorkLog $workLog)
    {
        if ($workLog->audio_path && file_exists(public_path($workLog->audio_path))) {
            @unlink(public_path($workLog->audio_path));
        }

        $this->images->deleteAllFor($workLog);
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
