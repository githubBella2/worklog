<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Services\WorkLogExportService;
use Illuminate\Http\Request;

class WorkLogReportController extends Controller
{
    public function __construct(protected WorkLogExportService $exporter) {}

    /**
     * Printable KPI report (use browser Print > Save as PDF).
     */
    public function index(Request $request)
    {
        [$from, $to, $project] = $this->filters($request);

        return view('logs.report', [
            'logs' => $this->query($from, $to, $project)->get(),
            'projects' => WorkLog::whereNotNull('project')->where('project', '!=', '')->distinct()->pluck('project')->sort()->values(),
            'from' => $from,
            'to' => $to,
            'project' => $project,
            'preparedBy' => trim((string) $request->query('nama', '')),
        ]);
    }

    /**
     * Download the same selection as a ZIP (laporan.md + screenshots).
     */
    public function zip(Request $request)
    {
        [$from, $to, $project] = $this->filters($request);
        $logs = $this->query($from, $to, $project)->get();

        $tmp = $this->exporter->buildZip($logs, "Laporan Work Log {$from} s/d {$to}");

        return response()->download($tmp, "laporan-worklog-{$from}_{$to}.zip")->deleteFileAfterSend(true);
    }

    protected function filters(Request $request): array
    {
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        $project = $request->query('project') ?: null;

        return [$from, $to, $project];
    }

    protected function query(string $from, string $to, ?string $project)
    {
        return WorkLog::with('images')
            ->whereDate('logged_at', '>=', $from)
            ->whereDate('logged_at', '<=', $to)
            ->when($project, fn ($q) => $q->where('project', $project))
            ->orderBy('logged_at')
            ->orderBy('created_at');
    }
}
