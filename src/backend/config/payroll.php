<?php

return [
    'default_base_salary' => (int) env('PAYROLL_DEFAULT_BASE_SALARY', 300000),
    'transportation_allowance' => (int) env('PAYROLL_TRANSPORTATION_ALLOWANCE', 10000),
    'standard_work_days_per_month' => (int) env('PAYROLL_STANDARD_WORK_DAYS', 20),
    'standard_work_minutes_per_day' => (int) env('PAYROLL_STANDARD_WORK_MINUTES_PER_DAY', 480),

    'resident_tax_monthly' => (int) env('PAYROLL_RESIDENT_TAX_MONTHLY', 12000),
    'income_tax_annual_deduction' => (int) env('PAYROLL_INCOME_TAX_ANNUAL_DEDUCTION', 480000),

    /** 労使折半想定の「本人負担」率（概算。年度改定時は要更新） */
    'social_insurance_rates' => [
        'health_employee' => (float) env('PAYROLL_HEALTH_RATE', 0.0499),
        'pension_employee' => (float) env('PAYROLL_PENSION_RATE', 0.0915),
        'employment_employee' => (float) env('PAYROLL_EMPLOYMENT_RATE', 0.006),
    ],

    /**
     * 年収ベースの累進概算を月割り（簡易。源泉徴収税額表の代替ではない）
     *
     * @var list<array{up_to: int|null, rate: float}>
     */
    'income_tax_monthly_brackets' => [
        ['up_to' => 1950000, 'rate' => 0.0],
        ['up_to' => 3300000, 'rate' => 0.05],
        ['up_to' => 6950000, 'rate' => 0.10],
        ['up_to' => 9000000, 'rate' => 0.20],
        ['up_to' => 18000000, 'rate' => 0.23],
        ['up_to' => null, 'rate' => 0.33],
    ],
];
