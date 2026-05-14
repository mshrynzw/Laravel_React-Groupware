<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const TYPE_CLOCK_IN = 'clock_in';

    public const TYPE_CLOCK_OUT = 'clock_out';

    protected $fillable = [
        'user_id',
        'type',
        'recorded_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
