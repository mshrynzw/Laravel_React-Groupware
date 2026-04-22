<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
