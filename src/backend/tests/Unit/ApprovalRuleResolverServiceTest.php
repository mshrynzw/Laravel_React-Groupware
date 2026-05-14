<?php

namespace Tests\Unit;

use App\Models\ApprovalRule;
use App\Models\ApprovalRuleVersion;
use App\Models\User;
use App\Services\ApprovalRuleResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalRuleResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_highest_priority_matching_rule(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $low = ApprovalRule::create([
            'name' => '低優先',
            'request_type' => 'paid_leave',
            'scope_type' => 'global',
            'scope_id' => null,
            'priority' => 10,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        ApprovalRuleVersion::create([
            'approval_rule_id' => $low->id,
            'version_no' => 1,
            'conditions' => ['applicant_roles' => [User::ROLE_MEMBER]],
            'steps' => [['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN]],
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $high = ApprovalRule::create([
            'name' => '高優先',
            'request_type' => 'paid_leave',
            'scope_type' => 'global',
            'scope_id' => null,
            'priority' => 100,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $highVersion = ApprovalRuleVersion::create([
            'approval_rule_id' => $high->id,
            'version_no' => 1,
            'conditions' => ['applicant_roles' => [User::ROLE_MEMBER]],
            'steps' => [['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_SUPERADMIN]],
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $service = new ApprovalRuleResolverService;
        $resolved = $service->resolve($member, 'paid_leave', ['leave_days' => 2]);

        $this->assertNotNull($resolved);
        $this->assertSame($highVersion->id, $resolved->id);
    }

    public function test_resolve_returns_null_when_no_rule_matches_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $rule = ApprovalRule::create([
            'name' => '管理者のみ',
            'request_type' => 'paid_leave',
            'scope_type' => 'global',
            'scope_id' => null,
            'priority' => 10,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        ApprovalRuleVersion::create([
            'approval_rule_id' => $rule->id,
            'version_no' => 1,
            'conditions' => ['applicant_roles' => [User::ROLE_ADMIN]],
            'steps' => [['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN]],
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $service = new ApprovalRuleResolverService;
        $resolved = $service->resolve($member, 'paid_leave', ['leave_days' => 1]);

        $this->assertNull($resolved);
    }

    public function test_matches_conditions_leave_days_range(): void
    {
        $service = new ApprovalRuleResolverService;
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->assertTrue($service->matchesConditions(
            ['leave_days' => ['gte' => 2, 'lte' => 5]],
            $member,
            ['leave_days' => 3]
        ));
        $this->assertFalse($service->matchesConditions(
            ['leave_days' => ['gte' => 5]],
            $member,
            ['leave_days' => 2]
        ));
    }
}
