<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Models\WorkLogImage;
use App\Services\WorkLogExportService;
use App\Services\WorkLogImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkLogImageController extends Controller
{
    public function __construct(
        protected WorkLogImageService $images,
        protected WorkLogExportService $exporter
    ) {}

    /**
     * Add more before/after screenshots to an existing log.
     */
    public function store(Request $request, WorkLog $workLog)
    {
        $request->validate(WorkLogImageService::rules(), WorkLogImageService::messages());

        $stored = $this->images->storeFromRequest($request, $workLog);

        if ($stored === 0) {
            return redirect()->route('logs.edit', $workLog)
                ->with('warning', 'Tidak ada gambar yang tersimpan. Pilih gambar dulu, atau batas 10 gambar per kategori sudah tercapai.');
        }

        return redirect()->route('logs.edit', $workLog)
            ->with('success', "{$stored} screenshot berhasil ditambahkan.");
    }

    public function update(Request $request, WorkLog $workLog, WorkLogImage $image)
    {
        abort_unless($image->id_work_log === $workLog->id_work_log, 404);

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
        ]);

        $image->update(['caption' => $validated['caption'] ?? null]);

        return redirect()->route('logs.edit', $workLog)
            ->with('success', 'Caption diperbarui.');
    }

    public function destroy(WorkLog $workLog, WorkLogImage $image)
    {
        abort_unless($image->id_work_log === $workLog->id_work_log, 404);

        $this->images->delete($image);

        return redirect()->route('logs.edit', $workLog)
            ->with('success', 'Screenshot dihapus.');
    }

    /**
     * Download one log (summary + all screenshots) as a ZIP.
     */
    public function download(WorkLog $workLog)
    {
        $workLog->load('images');

        $tmp = $this->exporter->buildZip(collect([$workLog]), $workLog->title ?: 'Work Log');
        $name = 'worklog-'.$workLog->id_work_log.'-'.(Str::slug(Str::limit((string) $workLog->title, 40, '')) ?: 'log').'.zip';

        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }
}
