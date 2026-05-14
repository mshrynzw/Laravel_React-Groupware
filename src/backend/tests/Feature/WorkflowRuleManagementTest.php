<?php

namespace Tests\Feature;

use App\Models\ApprovalRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_create_workflow_rule(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/workflow/rules', [
            'name' => '有給_標準',
            'request_type' => 'paid_leave',
            'steps' => [
                [
                    'step_order' => 1,
                    'assignee_type' => 'role',
                    'assignee_value' => User::ROLE_ADMIN,
                ],
            ],
        ])->assertForbidden();
    }

    public function test_submit_creates_rule_resolution(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($admin);

        $create = $this->postJson('/api/workflow/rules', [
            'name' => '有給_標準',
            'request_type' => 'paid_leave',
            'priority' => 100,
            'conditions' => [
                'applicant_roles' => [User::ROLE_MEMBER],
            ],
            'steps' => [
                [
                    'step_order' => 1,
                    'assignee_type' => 'role',
                    'assignee_value' => User::ROLE_ADMIN,
                ],
            ],
        ])->assertCreated();

        $ruleId = (int) $create->json('rule.id');
        $this->postJson("/api/workflow/rules/{$ruleId}/publish")->assertOk();

        $this->actingAs($member);
        $requestResponse = $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
                'reason' => '私用',
            ],
        ])->assertCreated();

        $requestId = (int) $requestResponse->json('request.id');

        $this->postJson("/api/requests/{$requestId}/submit")->assertOk();

        $this->assertDatabaseHas('request_rule_resolutions', [
            'request_id' => $requestId,
            'approval_rule_id' => $ruleId,
        ]);

        $rule = ApprovalRule::query()->findOrFail($ruleId);
        $this->assertTrue($rule->versions()->where('is_published', true)->exists());
    }

    public function test_approve_updates_step_and_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($admin);
        $create = $this->postJson('/api/workflow/rules', [
            'name' => '有給_2段階承認',
            'request_type' => 'paid_leave',
            'priority' => 100,
            'steps' => [
                ['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN],
                ['step_order' => 2, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_SUPERADMIN],
            ],
        ])->assertCreated();
        $ruleId = (int) $create->json('rule.id');
        $this->postJson("/api/workflow/rules/{$ruleId}/publish")->assertOk();

        $this->actingAs($member);
        $requestId = (int) $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-05-10',
                'end_date' => '2026-05-10',
                'reason' => '通院',
            ],
        ])->assertCreated()->json('request.id');
        $this->postJson("/api/requests/{$requestId}/submit")->assertOk();

        $this->actingAs($admin);
        $this->postJson("/api/requests/{$requestId}/approve")->assertOk();
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => 'pending',
            'current_step' => 2,
        ]);

        $this->actingAs($superAdmin);
        $this->postJson("/api/requests/{$requestId}/approve")->assertOk();
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => 'approved',
            'current_step' => 2,
        ]);
    }

    public function test_rejected_request_cannot_be_approved_again(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($admin);
        $create = $this->postJson('/api/workflow/rules', [
            'name' => '有給_却下確認',
            'request_type' => 'paid_leave',
            'priority' => 100,
            'steps' => [
                ['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN],
            ],
        ])->assertCreated();
        $ruleId = (int) $create->json('rule.id');
        $this->postJson("/api/workflow/rules/{$ruleId}/publish")->assertOk();

        $this->actingAs($member);
        $requestId = (int) $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-05-15',
                'end_date' => '2026-05-15',
                'reason' => '家庭都合',
            ],
        ])->assertCreated()->json('request.id');
        $this->postJson("/api/requests/{$requestId}/submit")->assertOk();

        $this->actingAs($admin);
        $this->postJson("/api/requests/{$requestId}/reject", [
            'comment' => '繁忙期のため',
        ])->assertOk();

        $this->postJson("/api/requests/{$requestId}/approve")->assertStatus(422);
        $this->assertDatabaseHas('requests', [
            'id' => $requestId,
            'status' => 'rejected',
        ]);
    }

    public function test_request_creation_validates_date_range_and_reason(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-06-05',
                'end_date' => '2026-06-01',
                'reason' => '',
            ],
        ])->assertStatus(422);
    }

    public function test_non_current_approver_cannot_approve(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($admin);
        $create = $this->postJson('/api/workflow/rules', [
            'name' => '有給_承認順テスト',
            'request_type' => 'paid_leave',
            'priority' => 100,
            'steps' => [
                ['step_order' => 1, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_ADMIN],
                ['step_order' => 2, 'assignee_type' => 'role', 'assignee_value' => User::ROLE_SUPERADMIN],
            ],
        ])->assertCreated();
        $ruleId = (int) $create->json('rule.id');
        $this->postJson("/api/workflow/rules/{$ruleId}/publish")->assertOk();

        $this->actingAs($member);
        $requestId = (int) $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'reason' => 'テスト',
            ],
        ])->assertCreated()->json('request.id');
        $this->postJson("/api/requests/{$requestId}/submit")->assertOk();

        $this->actingAs($superAdmin);
        $this->postJson("/api/requests/{$requestId}/approve")->assertForbidden();
    }
}
