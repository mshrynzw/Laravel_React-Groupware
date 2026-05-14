<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRuleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_rule_id',
        'version_no',
        'conditions',
        'steps',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'steps' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rule_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
