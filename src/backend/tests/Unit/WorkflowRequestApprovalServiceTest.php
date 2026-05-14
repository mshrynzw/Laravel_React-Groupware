<?php

namespace Tests\Unit;

use App\Models\Approval;
use App\Models\User;
use App\Models\WorkflowRequest;
use App\Services\WorkflowRequestApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRequestApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_approval_when_actor_is_current_approver(): void
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

        Approval::create([
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
            'result' => null,
        ]);

        $service = new WorkflowRequestApprovalService;
        $approval = $service->findApprovableApproval($request->fresh(), $admin);

        $this->assertNotNull($approval);
        $this->assertSame(1, $approval->step_order);
    }

    public function test_returns_null_when_actor_is_not_current_approver(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $other = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $request = WorkflowRequest::create([
            'user_id' => $member->id,
            'type' => 'paid_leave',
            'status' => 'pending',
            'payload' => ['reason' => 'x'],
            'current_step' => 1,
        ]);

        Approval::create([
            'request_id' => $request->id,
            'step_order' => 1,
            'approver_user_id' => $admin->id,
            'result' => null,
        ]);

        $service = new WorkflowRequestApprovalService;
        $approval = $service->findApprovableApproval($request->fresh(), $other);

        $this->assertNull($approval);
    }
}
