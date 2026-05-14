<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\RequestRuleResolution;
use App\Models\User;
use App\Models\WorkflowRequest;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WorkflowRequestTransitionService
{
    public function __construct(private readonly ApprovalRuleResolverService $resolver) {}

    /**
     * 下書きを提出し、ルール解決・承認行・解決結果を保存して pending へ遷移する。
     *
     * @throws HttpExceptionInterface
     */
    public function submitDraft(WorkflowRequest $requestModel, User $actor): void
    {
        DB::transaction(function () use ($requestModel, $actor): void {
            $version = $this->resolver->resolve($actor, $requestModel->type, $requestModel->payload ?? []);
            if ($version === null) {
                abort(422, '適用可能な承認ルールがありません。');
            }

            $steps = collect($version->steps ?? [])
                ->sortBy('step_order')
                ->values();

            if ($steps->isEmpty()) {
                abort(422, '承認ステップが定義されていません。');
            }

            foreach ($steps as $step) {
                $approverId = $this->resolveApproverId($step, $requestModel);
                if ($approverId === null) {
                    abort(422, '承認者を解決できないステップが含まれています。');
                }
                Approval::create([
                    'request_id' => $requestModel->id,
                    'step_order' => $step['step_order'],
                    'approver_user_id' => $approverId,
                ]);
            }

            RequestRuleResolution::updateOrCreate(
                ['request_id' => $requestModel->id],
                [
                    'approval_rule_id' => $version->approval_rule_id,
                    'approval_rule_version_id' => $version->id,
                    'resolved_steps' => $steps->all(),
                    'matched_context' => [
                        'actor_user_id' => $actor->id,
                        'request_type' => $requestModel->type,
                    ],
                ]
            );

            $requestModel->update([
                'status' => 'pending',
                'current_step' => 1,
            ]);
        });
    }

    public function recordApproval(WorkflowRequest $requestModel, Approval $approval, ?string $comment = null): void
    {
        DB::transaction(function () use ($approval, $requestModel, $comment): void {
            $approval->update([
                'result' => 'approved',
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            $hasNext = $requestModel->approvals()
                ->where('step_order', '>', $requestModel->current_step)
                ->exists();

            if ($hasNext) {
                $requestModel->update([
                    'current_step' => $requestModel->current_step + 1,
                ]);
            } else {
                $requestModel->update([
                    'status' => 'approved',
                ]);
            }
        });
    }

    public function recordRejection(WorkflowRequest $requestModel, Approval $approval, ?string $comment = null): void
    {
        DB::transaction(function () use ($approval, $requestModel, $comment): void {
            $approval->update([
                'result' => 'rejected',
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            $requestModel->update([
                'status' => 'rejected',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $step
     */
    public function resolveApproverId(array $step, WorkflowRequest $requestModel): ?int
    {
        $type = $step['assignee_type'] ?? null;
        $value = $step['assignee_value'] ?? null;
        if (! is_string($type) || ! is_string($value)) {
            return null;
        }

        if ($type === 'user') {
            return (int) $value;
        }

        if ($type === 'role') {
            return User::query()
                ->where('role', $value)
                ->orderBy('id')
                ->value('id');
        }

        if ($type === 'group_manager') {
            return User::query()
                ->where('role', User::ROLE_ADMIN)
                ->whereHas('groups', function ($q) use ($requestModel): void {
                    $q->whereIn('groups.id', function ($sub) use ($requestModel): void {
                        $sub->select('group_id')
                            ->from('group_user')
                            ->where('user_id', $requestModel->user_id);
                    });
                })
                ->orderBy('id')
                ->value('id');
        }

        return null;
    }
}
