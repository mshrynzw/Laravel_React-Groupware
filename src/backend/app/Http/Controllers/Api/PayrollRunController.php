<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollRunRequest;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use App\Models\User;
use App\Services\PayrollCalculationService;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
    ) {}

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $year = (int) $request->validated('year');
        $month = (int) $request->validated('month');

        $existing = PayrollRun::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', PayrollRun::STATUS_COMPLETED)
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'この対象月は既に給与計算が完了しています。',
            ], 409);
        }

        $run = PayrollRun::create([
            'period_year' => $year,
            'period_month' => $month,
            'status' => PayrollRun::STATUS_PENDING,
            'executed_by' => $actor->id,
            'executed_at' => null,
        ]);

        AuditLogger::log($request, 'payroll.run_started', $run, [
            'period_year' => $year,
            'period_month' => $month,
        ]);

        try {
            DB::transaction(function () use ($run, $year, $month): void {
                $users = User::query()->orderBy('id')->get();

                foreach ($users as $user) {
                    $result = $this->calculationService->calculateForUser($user, $year, $month);

                    PayrollSlip::create([
                        'payroll_run_id' => $run->id,
                        'user_id' => $user->id,
                        'gross_amount' => $result['gross_amount'],
                        'net_amount' => $result['net_amount'],
                        'breakdown' => $result['breakdown'],
                    ]);
                }

                $run->status = PayrollRun::STATUS_COMPLETED;
                $run->executed_at = now();
                $run->save();
            });
        } catch (\Throwable $e) {
            $run->status = PayrollRun::STATUS_FAILED;
            $run->save();

            throw $e;
        }

        $run->refresh()->load(['executor:id,name,email']);

        AuditLogger::log($request, 'payroll.run_completed', $run, [
            'period_year' => $year,
            'period_month' => $month,
            'slip_count' => $run->slips()->count(),
        ]);

        return response()->json([
            'message' => '給与計算を完了しました。',
            'data' => $run,
        ], 201);
    }

    public function show(Request $request, PayrollRun $payroll_run): JsonResponse
    {
        $this->ensurePayrollAdmin($request->user());

        return response()->json(
            $payroll_run->load(['executor:id,name,email'])
        );
    }

    private function ensurePayrollAdmin(?User $user): void
    {
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true)) {
            abort(403);
        }
    }
}
