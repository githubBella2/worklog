<?php

namespace App\Services;

use App\Models\WorkLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class WorkLogExportService
{
    /**
     * Build a ZIP (laporan.md + folder per log with before/after images).
     * Returns the temporary file path. Caller should delete it after sending.
     *
     * @param  Collection<int, WorkLog>  $logs
     */
    public function buildZip(Collection $logs, string $title): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP "zip" belum aktif. Aktifkan extension=zip di php.ini lalu restart server.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'wlzip');
        $zip = new ZipArchive;

        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file ZIP.');
        }

        $markdown = "# {$title}\n\n*Total log: {$logs->count()}*\n\n---\n\n";

        foreach ($logs as $log) {
            $folder = $this->folderName($log);
            $paths = ['before' => [], 'after' => []];

            foreach (['before', 'after'] as $kind) {
                $n = 0;
                foreach ($log->images->where('kind', $kind) as $image) {
                    $abs = Storage::disk('public')->path($image->path);
                    if (! is_file($abs)) {
                        continue;
                    }
                    $n++;
                    $ext = pathinfo($image->path, PATHINFO_EXTENSION) ?: 'png';
                    $label = $image->caption ?: pathinfo((string) $image->original_name, PATHINFO_FILENAME);
                    $slug = Str::slug(Str::limit((string) $label, 40, '')) ?: 'screenshot';
                    $entry = sprintf('%s/%s/%02d-%s.%s', $folder, $kind, $n, $slug, $ext);

                    $zip->addFile($abs, $entry);
                    $paths[$kind][] = ['path' => $entry, 'caption' => $image->caption];
                }
            }

            $markdown .= $this->logMarkdown($log, $paths);
        }

        $zip->addFromString('laporan.md', $markdown);
        $zip->close();

        return $tmp;
    }

    protected function folderName(WorkLog $log): string
    {
        $date = $log->logged_at ? $log->logged_at->format('Y-m-d') : 'tanpa-tanggal';
        $slug = Str::slug(Str::limit((string) $log->title, 40, '')) ?: 'log';

        return "{$date}_{$log->id_work_log}_{$slug}";
    }

    protected function logMarkdown(WorkLog $log, array $paths): string
    {
        $date = $log->logged_at ? $log->logged_at->format('d M Y') : '-';
        $project = $log->project ? $log->project.($log->module ? ' / '.$log->module : '') : 'General';

        $md = "## {$log->title}\n";
        $md .= "- **Project:** {$project}\n";
        $md .= "- **Tanggal:** {$date}\n";
        $md .= "- **Type:** `{$log->type}` | **Status:** `{$log->status}`\n\n";

        if ($log->problem) {
            $md .= "### Problem\n{$log->problem}\n\n";
        }

        $md .= "### BEFORE\n";
        if ($log->before) {
            $md .= $log->before."\n\n";
        } elseif (empty($paths['before'])) {
            $md .= "_Tidak dicantumkan_\n\n";
        }
        $md .= $this->imageLines($paths['before']);

        if (! empty($log->actions)) {
            $md .= "### WHAT I DID\n";
            foreach ($log->actions as $action) {
                $md .= "- {$action}\n";
            }
            $md .= "\n";
        }

        $md .= "### AFTER\n";
        if ($log->after) {
            $md .= $log->after."\n\n";
        } elseif (empty($paths['after'])) {
            $md .= "_Tidak dicantumkan_\n\n";
        }
        $md .= $this->imageLines($paths['after']);

        if ($log->impact) {
            $md .= "### IMPACT\n{$log->impact}\n\n";
        }

        if (! empty($log->testing)) {
            $md .= "### TESTING\n";
            foreach ($log->testing as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        return $md."---\n\n";
    }

    protected function imageLines(array $items): string
    {
        $out = '';
        foreach ($items as $item) {
            $out .= '!['.($item['caption'] ?: 'screenshot').']('.$item['path'].")\n\n";
        }

        return $out;
    }
}
