<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkLogImage extends Model
{
    protected $table = 'work_log_images';

    protected $primaryKey = 'id_work_log_image';

    protected $fillable = [
        'id_work_log',
        'kind',
        'path',
        'original_name',
        'caption',
        'sort_order',
    ];

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class, 'id_work_log', 'id_work_log');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->path));
    }
}
