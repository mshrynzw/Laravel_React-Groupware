<?php

namespace App\Services;

use App\Models\ApprovalRule;
use App\Models\ApprovalRuleVersion;
use App\Models\User;

class ApprovalRuleResolverService
{
    public function resolve(User $applicant, string $requestType, array $payload): ?ApprovalRuleVersion
    {
        $rules = ApprovalRule::query()
            ->where('request_type', $requestType)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->with(['versions' => fn ($q) => $q->where('is_published', true)->orderByDesc('version_no')])
            ->get();

        foreach ($rules as $rule) {
            $version = $rule->versions->first();
            if ($version === null) {
                continue;
            }

            if ($this->matchesConditions($version->conditions ?? [], $applicant, $payload)) {
                return $version;
            }
        }

        return null;
    }

    public function matchesConditions(array $conditions, User $applicant, array $payload): bool
    {
        $leaveDays = (int) ($payload['leave_days'] ?? 0);
        $range = $conditions['leave_days'] ?? null;
        if (is_array($range)) {
            if (isset($range['gte']) && $leaveDays < (int) $range['gte']) {
                return false;
            }
            if (isset($range['lte']) && $leaveDays > (int) $range['lte']) {
                return false;
            }
        }

        $roles = $conditions['applicant_roles'] ?? null;
        if (is_array($roles) && ! in_array($applicant->role, $roles, true)) {
            return false;
        }

        return true;
    }
}
