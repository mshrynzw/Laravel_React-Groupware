<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class PayrollCalculationService
{
    private const TZ = 'Asia/Tokyo';

    public function __construct(
        private readonly AttendanceSummaryService $attendanceSummary,
        private readonly PayrollTaxService $payrollTax,
    ) {}

    /**
     * @return array{
     *   gross_amount: string,
     *   net_amount: string,
     *   breakdown: array<string, mixed>
     * }
     */
    public function calculateForUser(User $user, int $year, int $month): array
    {
        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = CarbonImmutable::parse($fromDate, self::TZ)->endOfMonth()->toDateString();

        $fromStart = CarbonImmutable::parse($fromDate, self::TZ)->startOfDay();
        $toEnd = CarbonImmutable::parse($toDate, self::TZ)->endOfDay();

        $records = $this->attendanceSummary->recordsForSummary($user->id, $fromStart, $toEnd);
        $summary = $this->attendanceSummary->summarizeFromRecords($fromDate, $toDate, $records);

        $workDays = 0;
        $absenceDays = 0;
        foreach ($summary['days'] as $day) {
            if (($day['work_minutes'] ?? 0) > 0) {
                $workDays++;
            } elseif (($day['status'] ?? 'none') === 'none') {
                $absenceDays++;
            }
        }

        $baseSalary = (int) config('payroll.default_base_salary', 300000);
        $standardDays = max(1, (int) config('payroll.standard_work_days_per_month', 20));
        $standardMinutesPerDay = (int) config('payroll.standard_work_minutes_per_day', 480);
        $expectedMinutes = $workDays * $standardMinutesPerDay;
        $overtimeMinutes = max(0, (int) $summary['total_work_minutes'] - $expectedMinutes);
        $minuteRate = $baseSalary / $standardDays / $standardMinutesPerDay;
        $overtimePay = (int) round($overtimeMinutes * $minuteRate);

        $transportation = $workDays > 0
            ? (int) config('payroll.transportation_allowance', 10000)
            : 0;

        $allowances = [
            ['name' => '残業手当', 'amount' => $overtimePay],
            ['name' => '通勤手当', 'amount' => $transportation],
        ];

        $gross = $baseSalary + $overtimePay + $transportation;

        $tax = $this->payrollTax->calculateDeductions($gross, $year, $month);
        $si = $tax['social_insurance'];

        $deductions = [
            ['name' => '健康保険（本人）', 'amount' => $si['health']],
            ['name' => '厚生年金（本人）', 'amount' => $si['pension']],
            ['name' => '雇用保険（本人）', 'amount' => $si['employment']],
            ['name' => '所得税（概算）', 'amount' => $tax['income_tax']],
            ['name' => '住民税', 'amount' => $tax['resident_tax']],
        ];

        $deductionTotal = array_sum(array_column($deductions, 'amount'));
        $net = max(0, $gross - $deductionTotal);

        $breakdown = [
            'schema_version' => 2,
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'tax_detail' => [
                'taxable_income' => $tax['taxable_income'],
                'social_insurance' => $si,
                'income_tax' => $tax['income_tax'],
                'resident_tax' => $tax['resident_tax'],
                'note' => '簡易計算（人事・労務の確認を前提とする）',
            ],
            'attendance' => [
                'work_days' => $workDays,
                'absence_days' => $absenceDays,
                'total_work_minutes' => (int) $summary['total_work_minutes'],
            ],
        ];

        return [
            'gross_amount' => number_format($gross, 2, '.', ''),
            'net_amount' => number_format($net, 2, '.', ''),
            'breakdown' => $breakdown,
        ];
    }
}
