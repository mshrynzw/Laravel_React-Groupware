<?php

namespace App\Services;

/**
 * 給与控除の簡易計算（年度・等級は config で調整。実務利用は人事・労務確認前提）。
 */
class PayrollTaxService
{
    /**
     * @return array{
     *   social_insurance: array{health: int, pension: int, employment: int, total: int},
     *   income_tax: int,
     *   resident_tax: int,
     *   taxable_income: int
     * }
     */
    public function calculateDeductions(int $gross, int $year, int $month): array
    {
        $rates = config('payroll.social_insurance_rates', []);
        $health = (int) round($gross * (float) ($rates['health_employee'] ?? 0.0499));
        $pension = (int) round($gross * (float) ($rates['pension_employee'] ?? 0.0915));
        $employment = (int) round($gross * (float) ($rates['employment_employee'] ?? 0.006));
        $socialTotal = $health + $pension + $employment;

        $taxable = max(0, $gross - $socialTotal);
        $incomeTax = $this->estimateMonthlyIncomeTax($taxable);
        $residentTax = (int) config('payroll.resident_tax_monthly', 12000);

        return [
            'social_insurance' => [
                'health' => $health,
                'pension' => $pension,
                'employment' => $employment,
                'total' => $socialTotal,
            ],
            'income_tax' => $incomeTax,
            'resident_tax' => $residentTax,
            'taxable_income' => $taxable,
        ];
    }

    private function estimateMonthlyIncomeTax(int $taxableMonthly): int
    {
        /** @var list<array{up_to: int|null, rate: float, deduction: int}> $brackets */
        $brackets = config('payroll.income_tax_monthly_brackets', []);
        $annual = $taxableMonthly * 12;
        $taxAnnual = 0.0;
        $prev = 0;

        foreach ($brackets as $row) {
            $upTo = $row['up_to'];
            $cap = $upTo === null ? $annual : min($annual, (int) $upTo);
            if ($cap <= $prev) {
                continue;
            }
            $slice = $cap - $prev;
            $taxAnnual += $slice * (float) $row['rate'];
            $prev = $cap;
            if ($upTo === null || $annual <= (int) $upTo) {
                break;
            }
        }

        $deduction = (int) config('payroll.income_tax_annual_deduction', 0);

        return max(0, (int) round(($taxAnnual - $deduction) / 12));
    }
}
