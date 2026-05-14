<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\User;
use App\Models\WorkflowRequest;

class WorkflowRequestApprovalService
{
    public function findApprovableApproval(WorkflowRequest $request, User $actor): ?Approval
    {
        if ($request->status !== 'pending') {
            return null;
        }

        return $request->approvals()
            ->where('step_order', $request->current_step)
            ->where('approver_user_id', $actor->id)
            ->whereNull('result')
            ->first();
    }
}
