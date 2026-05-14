<?php

namespace Tests\Unit;

use App\Models\Approval;
use App\Models\ApprovalRule;
use App\Models\ApprovalRuleVersion;
use App\Models\User;
use App\Models\WorkflowRequest;
use App\Services\WorkflowRequestTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRequestTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_draft_sets_pending_and_resolution(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $rule = ApprovalRule::create([
            'name' => '有給_単体提出',
            'request_type' => 'paid_leave',
            'scope_type' => 'global',
            'scope_id' => null,
            'priority' => 100,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        ApprovalRuleVersion::create([
            'approval_rule_id' => $rule->id,
            'version_no' => 1,
            'conditions' => ['applicant_roles' => [User::ROLE_MEMBER]],
            'steps' => [
                ['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN],
            ],
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'draft',
            'payload' => [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-02',
                'reason' => 'テスト',
                'leave_days' => 2,
            ],
            'current_step' => 0,
        ]);

        $service = app(WorkflowRequestTransitionService::class);
        $service->submitDraft($request, $member);

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame(1, $request->current_step);
        $this->assertDatabaseHas('approvals', [
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('request_rule_resolutions', [
            'request_id' => $request->id,
            'approval_rule_id' => $rule->id,
        ]);
    }

    public function test_record_approval_final_step_sets_approved(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'pending',
            'payload' => ['reason' => 'x'],
            'current_step' => 1,
        ]);

        $approval = Approval::create([
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
            'result' => null,
        ]);

        $service = app(WorkflowRequestTransitionService::class);
        $service->recordApproval($request, $approval, '了解しました');

        $request->refresh();
        $approval->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame('approved', $approval->result);
        $this->assertSame('了解しました', $approval->comment);
    }

    public function test_record_approval_with_next_step_advances_current_step(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $super = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'pending',
            'payload' => ['reason' => 'x'],
            'current_step' => 1,
        ]);

        $a1 = Approval::create([
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
            'result' => null,
        ]);
        Approval::create([
            'request_id' => $request->id,
            'step_order' => 2,
            'approver_user_id' => $super->id,
            'result' => null,
        ]);

        $service = app(WorkflowRequestTransitionService::class);
        $service->recordApproval($request, $a1, null);

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame(2, $request->current_step);
    }

    public function test_record_rejection_sets_status_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'pending',
            'payload' => ['reason' => 'x'],
            'current_step' => 1,
        ]);

        $approval = Approval::create([
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
            'result' => null,
        ]);

        $service = app(WorkflowRequestTransitionService::class);
        $service->recordRejection($request, $approval, '不可');

        $request->refresh();
        $approval->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('rejected', $approval->result);
        $this->assertSame('不可', $approval->comment);
    }

    public function test_resolve_approver_id_for_role_returns_first_matching_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'draft',
            'payload' => [],
            'current_step' => 0,
        ]);

        $service = app(WorkflowRequestTransitionService::class);
        $id = $service->resolveApproverId([
            'step_order' => 1,
            'assignee_type' => 'role',
            'assignee_value' => User::ROLE_ADMIN,
        ], $request);

        $this->assertSame($admin->id, $id);
    }
}
