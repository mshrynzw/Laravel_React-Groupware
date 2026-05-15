<?php

namespace Tests\Unit;

use App\Services\PayrollTaxService;
use Tests\TestCase;

class PayrollTaxServiceTest extends TestCase
{
    public function test_calculate_deductions_returns_positive_amounts(): void
    {
        $service = new PayrollTaxService;
        $result = $service->calculateDeductions(350000, 2026, 4);

        $this->assertGreaterThan(0, $result['social_insurance']['total']);
        $this->assertGreaterThanOrEqual(0, $result['income_tax']);
        $this->assertSame(12000, $result['resident_tax']);
        $this->assertLessThan(350000, $result['taxable_income']);
    }

    public function test_zero_gross_yields_minimal_tax(): void
    {
        $service = new PayrollTaxService;
        $result = $service->calculateDeductions(0, 2026, 1);

        $this->assertSame(0, $result['social_insurance']['total']);
        $this->assertSame(0, $result['income_tax']);
    }
}
