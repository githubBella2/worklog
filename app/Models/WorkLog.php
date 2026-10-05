<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    use HasFactory;

    protected $table = 'work_logs';
    protected $primaryKey = 'id_work_log';

    protected $fillable = [
        'title',
        'project',
        'module',
        'type',
        'status',
        'problem',
        'before',
        'actions',
        'after',
        'impact',
        'testing',
        'technologies',
        'next_step',
        'tags',
        'logged_at',
        'transcript',
        'audio_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actions' => 'array',
            'testing' => 'array',
            'technologies' => 'array',
            'tags' => 'array',
            'logged_at' => 'date',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (WorkLog $workLog) {
            if (empty($workLog->logged_at)) {
                $workLog->logged_at = now()->toDateString();
            }
        });
    }
}
