<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestRuleResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'approval_rule_id',
        'approval_rule_version_id',
        'resolved_steps',
        'matched_context',
    ];

    protected function casts(): array
    {
        return [
            'resolved_steps' => 'array',
            'matched_context' => 'array',
        ];
    }

    public function workflowRequest(): BelongsTo
    {
        return $this->belongsTo(WorkflowRequest::class, 'request_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rule_id');
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(ApprovalRuleVersion::class, 'approval_rule_version_id');
    }
}
