<?php

namespace App\Services;

use App\Models\WorkLog;
use App\Models\WorkLogImage;
use Illuminate\Support\Facades\Storage;

class WorkLogImageService
{
    public const MAX_PER_KIND = 10;

    public const KINDS = ['before', 'after'];

    /**
     * Validation rules for before_images[] and after_images[].
     */
    public static function rules(): array
    {
        $rules = [];
        foreach (self::KINDS as $kind) {
            $rules["{$kind}_images"] = ['nullable', 'array', 'max:'.self::MAX_PER_KIND];
            $rules["{$kind}_images.*"] = ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return $rules;
    }

    public static function messages(): array
    {
        $messages = [];
        foreach (self::KINDS as $kind) {
            $label = strtoupper($kind);
            $messages["{$kind}_images.max"] = "Screenshot {$label} maksimal ".self::MAX_PER_KIND.' gambar.';
            $messages["{$kind}_images.*.mimes"] = "Screenshot {$label} harus berformat jpg, jpeg, png, atau webp.";
            $messages["{$kind}_images.*.max"] = "Ukuran tiap screenshot {$label} maksimal 5 MB.";
            $messages["{$kind}_images.*.file"] = "Salah satu file screenshot {$label} gagal diunggah (cek batas upload PHP).";
            $messages["{$kind}_images.*.uploaded"] = "Salah satu file screenshot {$label} gagal diunggah (cek upload_max_filesize / post_max_size di php.ini).";
        }

        return $messages;
    }

    /**
     * Save uploaded files for one kind. Returns how many were stored.
     */
    public function storeMany(WorkLog $log, string $kind, array $files): int
    {
        $existing = $log->images()->where('kind', $kind)->count();
        $slots = max(0, self::MAX_PER_KIND - $existing);
        $order = (int) $log->images()->where('kind', $kind)->max('sort_order');
        $stored = 0;

        foreach (array_slice(array_values($files), 0, $slots) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("work-logs/{$log->id_work_log}", 'public');

            $log->images()->create([
                'kind' => $kind,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => ++$order,
            ]);
            $stored++;
        }

        return $stored;
    }

    /**
     * Save both before_images and after_images from a request.
     */
    public function storeFromRequest($request, WorkLog $log): int
    {
        $total = 0;
        foreach (self::KINDS as $kind) {
            $files = $request->file("{$kind}_images", []);
            if (! empty($files)) {
                $total += $this->storeMany($log, $kind, $files);
            }
        }

        return $total;
    }

    public function delete(WorkLogImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    public function deleteAllFor(WorkLog $log): void
    {
        Storage::disk('public')->deleteDirectory("work-logs/{$log->id_work_log}");
    }
}
