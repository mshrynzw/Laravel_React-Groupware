<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollSlip;
use App\Models\User;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollSlipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = PayrollSlip::query()
            ->with(['run:id,period_year,period_month,status,executed_at', 'user:id,name,email'])
            ->orderByDesc('id');

        if ($this->isPayrollAdmin($actor)) {
            if (! empty($validated['user_id'])) {
                $query->where('user_id', (int) $validated['user_id']);
            }
        } else {
            $query->where('user_id', $actor->id);
        }

        if (! empty($validated['year'])) {
            $query->whereHas('run', fn ($q) => $q->where('period_year', (int) $validated['year']));
        }
        if (! empty($validated['month'])) {
            $query->whereHas('run', fn ($q) => $q->where('period_month', (int) $validated['month']));
        }

        $perPage = min(100, max(1, (int) ($validated['per_page'] ?? 24)));

        return response()->json($query->paginate($perPage));
    }

    public function show(Request $request, PayrollSlip $payroll_slip): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->ensureCanViewSlip($actor, $payroll_slip);

        AuditLogger::log($request, 'payroll.slip_viewed', $payroll_slip, [
            'payroll_slip_id' => $payroll_slip->id,
            'user_id' => $payroll_slip->user_id,
            'period_year' => $payroll_slip->run?->period_year,
            'period_month' => $payroll_slip->run?->period_month,
        ]);

        return response()->json(
            $payroll_slip->load(['run:id,period_year,period_month,status,executed_at', 'user:id,name,email'])
        );
    }

    public function download(Request $request, PayrollSlip $payroll_slip): Response
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->ensureCanViewSlip($actor, $payroll_slip);

        $payroll_slip->load(['run', 'user:id,name,email']);
        $run = $payroll_slip->run;
        $breakdown = $payroll_slip->breakdown ?? [];
        $deductions = $breakdown['deductions'] ?? [];
        $deductionTotal = array_sum(array_column($deductions, 'amount'));

        AuditLogger::log($request, 'payroll.slip_downloaded', $payroll_slip, [
            'payroll_slip_id' => $payroll_slip->id,
            'user_id' => $payroll_slip->user_id,
        ]);

        $periodLabel = $run
            ? sprintf('%d年%d月', $run->period_year, $run->period_month)
            : '';

        $pdf = Pdf::loadView('payroll.slip', [
            'periodLabel' => $periodLabel,
            'userName' => $payroll_slip->user?->name ?? '',
            'gross' => (float) $payroll_slip->gross_amount,
            'net' => (float) $payroll_slip->net_amount,
            'deductionTotal' => $deductionTotal,
            'baseSalary' => (int) ($breakdown['base_salary'] ?? 0),
            'allowances' => $breakdown['allowances'] ?? [],
            'deductions' => $deductions,
            'attendance' => $breakdown['attendance'] ?? [],
        ]);

        $filename = sprintf('payroll-%s-%d.pdf', $periodLabel !== '' ? "{$run->period_year}-{$run->period_month}" : 'slip', $payroll_slip->id);

        return $pdf->download($filename);
    }

    private function ensureCanViewSlip(User $actor, PayrollSlip $slip): void
    {
        if ($this->isPayrollAdmin($actor)) {
            return;
        }

        if ($slip->user_id === $actor->id) {
            return;
        }

        abort(403);
    }

    private function isPayrollAdmin(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }
}
