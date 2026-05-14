<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreApprovalRuleRequest;
use App\Http\Requests\Workflow\UpdateApprovalRuleRequest;
use App\Models\ApprovalRule;
use App\Models\ApprovalRuleVersion;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApprovalRuleController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();
        $this->ensureCanManageWorkflowRules($actor);

        $rules = ApprovalRule::query()
            ->with('versions')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->paginate((int) request('per_page', 15));

        return response()->json($rules);
    }

    public function store(StoreApprovalRuleRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanManageWorkflowRules($actor);
        $validated = $request->validated();

        $rule = DB::transaction(function () use ($validated, $actor): ApprovalRule {
            $rule = ApprovalRule::create([
                'name' => $validated['name'],
                'request_type' => $validated['request_type'],
                'scope_type' => $validated['scope_type'] ?? null,
                'scope_id' => $validated['scope_id'] ?? null,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            ApprovalRuleVersion::create([
                'approval_rule_id' => $rule->id,
                'version_no' => 1,
                'conditions' => $validated['conditions'] ?? [],
                'steps' => $validated['steps'],
                'is_published' => false,
                'created_by' => $actor->id,
            ]);

            return $rule->load('versions');
        });

        AuditLogger::log($request, 'workflow_rule.created', $rule, [
            'request_type' => $rule->request_type,
            'priority' => $rule->priority,
        ]);

        return response()->json([
            'message' => '承認ルールを作成しました。',
            'rule' => $rule,
        ], 201);
    }

    public function update(UpdateApprovalRuleRequest $request, ApprovalRule $rule): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanManageWorkflowRules($actor);

        $validated = $request->validated();
        $rule->fill(collect($validated)->only(['name', 'scope_type', 'scope_id', 'priority', 'is_active'])->toArray());
        $rule->updated_by = $actor->id;
        $rule->save();

        if (array_key_exists('conditions', $validated) || array_key_exists('steps', $validated)) {
            $nextVersion = ((int) $rule->versions()->max('version_no')) + 1;
            ApprovalRuleVersion::create([
                'approval_rule_id' => $rule->id,
                'version_no' => $nextVersion,
                'conditions' => $validated['conditions'] ?? [],
                'steps' => $validated['steps'] ?? [],
                'is_published' => false,
                'created_by' => $actor->id,
            ]);
        }

        AuditLogger::log($request, 'workflow_rule.updated', $rule, [
            'priority' => $rule->priority,
            'is_active' => $rule->is_active,
        ]);

        return response()->json([
            'message' => '承認ルールを更新しました。',
            'rule' => $rule->load('versions'),
        ]);
    }

    public function publish(ApprovalRule $rule): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();
        $this->ensureCanManageWorkflowRules($actor);

        /** @var ApprovalRuleVersion|null $version */
        $version = $rule->versions()->latest('version_no')->first();
        if ($version === null) {
            abort(422, '公開対象のバージョンが存在しません。');
        }

        $rule->versions()->update(['is_published' => false]);
        $version->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $rule->update(['updated_by' => $actor->id]);

        AuditLogger::log(request(), 'workflow_rule.published', $rule, [
            'version_no' => $version->version_no,
        ]);

        return response()->json(['message' => '承認ルールを公開しました。']);
    }

    public function activate(ApprovalRule $rule): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();
        $this->ensureCanManageWorkflowRules($actor);

        $rule->update([
            'is_active' => true,
            'updated_by' => $actor->id,
        ]);
        AuditLogger::log(request(), 'workflow_rule.activated', $rule);

        return response()->json(['message' => '承認ルールを有効化しました。']);
    }

    public function deactivate(ApprovalRule $rule): JsonResponse
    {
        /** @var User $actor */
        $actor = request()->user();
        $this->ensureCanManageWorkflowRules($actor);

        $rule->update([
            'is_active' => false,
            'updated_by' => $actor->id,
        ]);
        AuditLogger::log(request(), 'workflow_rule.deactivated', $rule);

        return response()->json(['message' => '承認ルールを無効化しました。']);
    }

    private function ensureCanManageWorkflowRules(User $actor): void
    {
        if (! $actor->canManageWorkflowRules()) {
            abort(403);
        }
    }
}
