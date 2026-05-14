<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkflowRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'payload',
        'current_step',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'request_id');
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(RequestRuleResolution::class, 'request_id');
    }
}
