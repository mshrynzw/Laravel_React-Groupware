<?php

namespace Tests\Feature;

use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6PayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_run_payroll(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/payroll/runs', ['year' => 2026, 'month' => 4])
            ->assertForbidden();
    }

    public function test_admin_can_run_payroll_and_list_slips(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($admin);
        $this->postJson('/api/payroll/runs', ['year' => 2026, 'month' => 4])
            ->assertCreated()
            ->assertJsonPath('data.status', PayrollRun::STATUS_COMPLETED);

        $this->assertDatabaseHas('payroll_slips', ['user_id' => $member->id]);

        $this->actingAs($member);
        $this->getJson('/api/payroll/slips?year=2026&month=4')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $member->id);
    }

    public function test_duplicate_completed_run_returns_409(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        PayrollRun::create([
            'period_year' => 2026,
            'period_month' => 3,
            'status' => PayrollRun::STATUS_COMPLETED,
            'executed_by' => $admin->id,
            'executed_at' => now(),
        ]);

        $this->actingAs($admin);
        $this->postJson('/api/payroll/runs', ['year' => 2026, 'month' => 3])
            ->assertStatus(409);
    }

    public function test_member_cannot_view_other_users_slip(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $b = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $run = PayrollRun::create([
            'period_year' => 2026,
            'period_month' => 1,
            'status' => PayrollRun::STATUS_COMPLETED,
            'executed_by' => $admin->id,
            'executed_at' => now(),
        ]);

        $slip = PayrollSlip::create([
            'payroll_run_id' => $run->id,
            'user_id' => $b->id,
            'gross_amount' => '300000.00',
            'net_amount' => '250000.00',
            'breakdown' => ['schema_version' => 1],
        ]);

        $this->actingAs($a);
        $this->getJson("/api/payroll/slips/{$slip->id}")->assertForbidden();
    }

    public function test_member_can_view_own_slip(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $run = PayrollRun::create([
            'period_year' => 2026,
            'period_month' => 2,
            'status' => PayrollRun::STATUS_COMPLETED,
            'executed_by' => $admin->id,
            'executed_at' => now(),
        ]);

        $slip = PayrollSlip::create([
            'payroll_run_id' => $run->id,
            'user_id' => $member->id,
            'gross_amount' => '310000.00',
            'net_amount' => '260000.00',
            'breakdown' => [
                'schema_version' => 1,
                'base_salary' => 300000,
                'allowances' => [],
                'deductions' => [],
                'attendance' => ['work_days' => 0, 'absence_days' => 0],
            ],
        ]);

        $this->actingAs($member);
        $this->getJson("/api/payroll/slips/{$slip->id}")
            ->assertOk()
            ->assertJsonPath('id', $slip->id);
    }

    public function test_member_can_download_own_slip_pdf(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $run = PayrollRun::create([
            'period_year' => 2026,
            'period_month' => 5,
            'status' => PayrollRun::STATUS_COMPLETED,
            'executed_by' => $admin->id,
            'executed_at' => now(),
        ]);

        $slip = PayrollSlip::create([
            'payroll_run_id' => $run->id,
            'user_id' => $member->id,
            'gross_amount' => '310000.00',
            'net_amount' => '260000.00',
            'breakdown' => [
                'schema_version' => 2,
                'base_salary' => 300000,
                'allowances' => [],
                'deductions' => [['name' => '健康保険（本人）', 'amount' => 1000]],
                'attendance' => ['work_days' => 1, 'absence_days' => 0],
            ],
        ]);

        $this->actingAs($member);
        $this->get("/api/payroll/slips/{$slip->id}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payroll_run_uses_schema_version_2_breakdown(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->postJson('/api/payroll/runs', ['year' => 2026, 'month' => 6])->assertCreated();

        $slip = PayrollSlip::query()->first();
        $this->assertNotNull($slip);
        $this->assertSame(2, $slip->breakdown['schema_version'] ?? null);
        $this->assertArrayHasKey('tax_detail', $slip->breakdown);
    }
}
