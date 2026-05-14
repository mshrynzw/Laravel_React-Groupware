<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreWorkflowRequest;
use App\Models\Approval;
use App\Models\User;
use App\Models\WorkflowRequest;
use App\Services\WorkflowRequestApprovalService;
use App\Services\WorkflowRequestTransitionService;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowRequestController extends Controller
{
    public function __construct(
        private readonly WorkflowRequestApprovalService $approvalService,
        private readonly WorkflowRequestTransitionService $transitionService,
    ) {}

    private const REQUEST_RELATIONS = [
        'applicant:id,name,email,role',
        'approvals.approver:id,name,email,role',
        'resolution',
    ];

    public function index(): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();

        $query = WorkflowRequest::query()
            ->with(self::REQUEST_RELATIONS)
            ->orderByDesc('id');

        if (request('mode') === 'pending_approval') {
            $query->whereHas('approvals', function ($q) use ($actor): void {
                $q->where('approver_user_id', $actor->id)
                    ->whereNull('result');
            });
        } else {
            $query->where('user_id', $actor->id);
        }

        return response()->json($query->paginate((int) request('per_page', 15)));
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $payload = $request->validated('payload');
        $startDate = CarbonImmutable::parse($payload['start_date']);
        $endDate = CarbonImmutable::parse($payload['end_date']);
        $payload['leave_days'] = $startDate->diffInDays($endDate) + 1;

        $workflowRequest = WorkflowRequest::create([
            'user_id' => $request->user()->id,
            'type' => $request->validated('type'),
            'payload' => $payload,
            'status' => 'draft',
            'current_step' => 0,
        ]);

        AuditLogger::log($request, 'request.created', $workflowRequest, [
            'type' => $workflowRequest->type,
            'status' => $workflowRequest->status,
        ]);

        return response()->json([
            'message' => '申請を下書き作成しました。',
            'request' => $workflowRequest,
        ], 201);
    }

    public function show(WorkflowRequest $requestModel): JsonResponse
    {
        $this->ensureCanView(request()->user(), $requestModel);

        return response()->json($requestModel->load(self::REQUEST_RELATIONS));
    }

    public function submit(WorkflowRequest $requestModel): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();
        if ($requestModel->user_id !== $actor->id) {
            abort(403);
        }

        if ($requestModel->status !== 'draft') {
            abort(422, '下書き状態の申請のみ提出できます。');
        }

        $this->transitionService->submitDraft($requestModel, $actor);

        AuditLogger::log(request(), 'request.submitted', $requestModel, [
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => '申請を提出しました。',
            'request' => $requestModel->fresh()->load(self::REQUEST_RELATIONS),
        ]);
    }

    public function resolution(WorkflowRequest $requestModel): JsonResponse
    {
        $this->ensureCanView(request()->user(), $requestModel);
        $resolution = $requestModel->resolution;
        if ($resolution === null) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'request_id' => $requestModel->id,
                'approval_rule_id' => $resolution->approval_rule_id,
                'approval_rule_version_id' => $resolution->approval_rule_version_id,
                'resolved_steps' => $resolution->resolved_steps,
            ],
        ]);
    }

    public function approve(Request $request, WorkflowRequest $requestModel): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if (in_array($requestModel->status, ['rejected', 'cancelled'], true)) {
            abort(422, '却下済みまたは取消済みの申請は承認できません。');
        }

        if ($requestModel->status !== 'pending') {
            abort(422, '承認待ち状態の申請のみ操作できます。');
        }

        $approval = $this->approvalService->findApprovableApproval($requestModel, $actor);
        if (! $approval instanceof Approval) {
            abort(403);
        }

        $this->transitionService->recordApproval(
            $requestModel,
            $approval,
            $request->string('comment')->toString() ?: null,
        );

        AuditLogger::log($request, 'request.approved', $requestModel, [
            'step_order' => $approval->step_order,
            'status' => $requestModel->fresh()->status,
            'comment' => $request->input('comment'),
        ]);

        return response()->json([
            'message' => '申請を承認しました。',
            'request' => $requestModel->fresh()->load(self::REQUEST_RELATIONS),
        ]);
    }

    public function reject(Request $request, WorkflowRequest $requestModel): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if (in_array($requestModel->status, ['rejected', 'cancelled'], true)) {
            abort(422, '却下済みまたは取消済みの申請です。');
        }

        if ($requestModel->status !== 'pending') {
            abort(422, '承認待ち状態の申請のみ操作できます。');
        }

        $approval = $this->approvalService->findApprovableApproval($requestModel, $actor);
        if (! $approval instanceof Approval) {
            abort(403);
        }

        $this->transitionService->recordRejection(
            $requestModel,
            $approval,
            $request->string('comment')->toString() ?: null,
        );

        AuditLogger::log($request, 'request.rejected', $requestModel, [
            'step_order' => $approval->step_order,
            'comment' => $request->input('comment'),
        ]);

        return response()->json([
            'message' => '申請を却下しました。',
            'request' => $requestModel->fresh()->load(self::REQUEST_RELATIONS),
        ]);
    }

    private function ensureCanView(User $actor, WorkflowRequest $requestModel): void
    {
        if ($actor->id === $requestModel->user_id || $actor->canManageWorkflowRules()) {
            return;
        }

        $canApprove = $requestModel->approvals()
            ->where('approver_user_id', $actor->id)
            ->exists();
        if (! $canApprove) {
            abort(403);
        }
    }
}
