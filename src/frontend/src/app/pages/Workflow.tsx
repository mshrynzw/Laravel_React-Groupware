import { useEffect, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { ArrowRight, Plus } from 'lucide-react';
import { apiGet, apiPost } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

type Approval = {
  id: number;
  step_order: number;
  approver_user_id: number | null;
  result: 'approved' | 'rejected' | null;
  comment?: string | null;
  approver?: {
    id: number;
    name: string;
    email: string;
    role: 'superadmin' | 'admin' | 'member';
  } | null;
};

type WorkflowRequest = {
  id: number;
  type: string;
  status: 'draft' | 'pending' | 'approved' | 'rejected' | 'cancelled';
  payload: {
    leave_days?: number;
    reason?: string;
    start_date?: string;
    end_date?: string;
  };
  current_step: number;
  created_at: string;
  user_id: number;
  applicant?: {
    id: number;
    name: string;
    email: string;
    role: 'superadmin' | 'admin' | 'member';
  } | null;
  approvals: Approval[];
};

type Paginated<T> = {
  data: T[];
};

const statusConfig = {
  pending: { label: '承認待ち', variant: 'warning' as const },
  approved: { label: '承認済み', variant: 'success' as const },
  rejected: { label: '却下', variant: 'error' as const },
};

const approverStatusConfig = {
  approved: { label: '承認', variant: 'success' as const },
  pending: { label: '承認待ち', variant: 'warning' as const },
  rejected: { label: '却下', variant: 'error' as const },
  waiting: { label: '待機中', variant: 'default' as const },
};

export function Workflow() {
  const { user } = useAuth();
  const [requests, setRequests] = useState<WorkflowRequest[]>([]);
  const [pendingApprovals, setPendingApprovals] = useState<WorkflowRequest[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');
  const [decisionDialog, setDecisionDialog] = useState<{ requestId: number; action: 'approve' | 'reject' } | null>(null);
  const [decisionComment, setDecisionComment] = useState('');

  const load = async () => {
    setLoading(true);
    try {
      const [myRes, pendingRes] = await Promise.all([
        apiGet<Paginated<WorkflowRequest>>('/api/requests'),
        apiGet<Paginated<WorkflowRequest>>('/api/requests?mode=pending_approval'),
      ]);
      setRequests(myRes.data);
      setPendingApprovals(pendingRes.data);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'ワークフローの取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load().catch(() => setMessage('ワークフローの取得に失敗しました。'));
  }, []);

  const createRequest = async () => {
    setMessage('');
    try {
      const created = await apiPost<{ request: WorkflowRequest }>('/api/requests', {
        type: 'paid_leave',
        payload: {
          start_date: startDate,
          end_date: endDate,
          reason,
        },
      });
      await apiPost(`/api/requests/${created.request.id}/submit`);
      setReason('');
      setStartDate('');
      setEndDate('');
      setMessage('申請を作成して提出しました。');
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : '申請作成に失敗しました。');
    }
  };

  const canActorApproveNow = (workflow: WorkflowRequest) => {
    if (workflow.status !== 'pending') {
      return false;
    }
    const current = getCurrentApproval(workflow);
    return Boolean(current && current.approver_user_id === user?.id);
  };

  const confirmDecision = async () => {
    if (!decisionDialog) {
      return;
    }
    const { requestId, action } = decisionDialog;
    const body = decisionComment.trim() ? { comment: decisionComment.trim() } : {};
    try {
      await apiPost(`/api/requests/${requestId}/${action}`, body);
      setMessage(action === 'approve' ? '申請を承認しました。' : '申請を却下しました。');
      setDecisionDialog(null);
      setDecisionComment('');
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : '承認操作に失敗しました。');
    }
  };

  const getApproverDisplay = (approval: Approval) => {
    if (approval.approver_user_id === user?.id) {
      return 'あなた';
    }
    if (approval.approver?.name) {
      return approval.approver.name;
    }
    if (approval.approver_user_id === null) {
      return '未解決';
    }
    return `承認者 #${approval.approver_user_id}`;
  };

  const getApproverStatus = (approval: Approval, workflow: WorkflowRequest) => {
    if (approval.result === 'approved') {
      return 'approved' as const;
    }
    if (approval.result === 'rejected') {
      return 'rejected' as const;
    }
    if (approval.step_order === workflow.current_step && workflow.status === 'pending') {
      return 'pending' as const;
    }
    return 'waiting' as const;
  };

  const formatDate = (date?: string) => {
    if (!date) {
      return '-';
    }
    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) {
      return date;
    }
    return parsed.toLocaleDateString('ja-JP');
  };

  const getCurrentApproval = (workflow: WorkflowRequest) =>
    workflow.approvals.find((approval) => approval.step_order === workflow.current_step && approval.result === null);

  const getCurrentApproverLabel = (workflow: WorkflowRequest) => {
    const current = getCurrentApproval(workflow);
    if (!current) {
      if (workflow.status === 'approved') {
        return '最終承認済み';
      }
      if (workflow.status === 'rejected') {
        return '却下済み';
      }
      return '-';
    }

    if (current.approver_user_id === user?.id) {
      return 'あなた';
    }
    if (current.approver?.name) {
      return current.approver.name;
    }
    if (current.approver_user_id === null) {
      return '未解決';
    }
    return `承認者 #${current.approver_user_id}`;
  };

  return (
    <div className="space-y-6 relative">
      {decisionDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <Card className="w-full max-w-md shadow-lg">
            <CardHeader>
              <CardTitle>{decisionDialog.action === 'approve' ? '承認' : '却下'}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <label className="text-sm block">
                コメント（任意）
                <textarea
                  className="w-full min-h-[96px] px-3 py-2 mt-1 bg-input-background border border-border rounded-lg text-sm"
                  value={decisionComment}
                  onChange={(e) => setDecisionComment(e.target.value)}
                  placeholder={decisionDialog.action === 'reject' ? '却下理由を入力できます' : '承認時のコメント'}
                />
              </label>
              <div className="flex justify-end gap-2 pt-2">
                <Button variant="outline" onClick={() => { setDecisionDialog(null); setDecisionComment(''); }}>
                  キャンセル
                </Button>
                <Button
                  variant={decisionDialog.action === 'reject' ? 'outline' : 'default'}
                  className={decisionDialog.action === 'reject' ? 'text-destructive' : ''}
                  onClick={() => void confirmDecision()}
                >
                  確定
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl">ワークフロー</h2>
        <Button className="gap-2" onClick={createRequest} disabled={loading || !startDate || !endDate || !reason.trim()}>
          <Plus className="w-4 h-4" />
          有給申請を作成
        </Button>
      </div>
      <Card>
        <CardHeader>
          <CardTitle>新規申請</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <label className="text-sm">
              開始日
              <input
                className="w-full px-3 py-2 mt-1 bg-input-background border border-border rounded-lg"
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
              />
            </label>
            <label className="text-sm">
              終了日
              <input
                className="w-full px-3 py-2 mt-1 bg-input-background border border-border rounded-lg"
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
              />
            </label>
            <label className="text-sm">
              理由
              <input
                className="w-full px-3 py-2 mt-1 bg-input-background border border-border rounded-lg"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder="私用のため"
              />
            </label>
          </div>
          {message && <p className="text-sm text-muted-foreground mt-3">{message}</p>}
        </CardContent>
      </Card>

      <div className="space-y-4">
        {requests.map((workflow) => (
          <Card key={workflow.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="mb-2">有給申請 #{workflow.id}</CardTitle>
                  <div className="flex items-center gap-3 text-sm text-muted-foreground">
                    <span>申請者: {workflow.user_id === user?.id ? 'あなた' : (workflow.applicant?.name ?? `ユーザー #${workflow.user_id}`)}</span>
                    <span>•</span>
                    <span>{new Date(workflow.created_at).toLocaleDateString('ja-JP')}</span>
                  </div>
                  <div className="mt-2 text-sm text-muted-foreground space-y-1">
                    <p>期間: {formatDate(workflow.payload.start_date)} 〜 {formatDate(workflow.payload.end_date)}</p>
                    <p>日数: {workflow.payload.leave_days ?? '-'} 日</p>
                    <p>理由: {workflow.payload.reason?.trim() ? workflow.payload.reason : '-'}</p>
                    <p>承認段階: {workflow.current_step > 0 ? `${workflow.current_step}段階目` : '-'}</p>
                    <p>次の承認者: {getCurrentApproverLabel(workflow)}</p>
                  </div>
                </div>
                <Badge variant={statusConfig[workflow.status] ? statusConfig[workflow.status].variant : 'default'}>
                  {statusConfig[workflow.status] ? statusConfig[workflow.status].label : workflow.status}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="bg-muted/30 rounded-lg p-4">
                <h4 className="text-sm mb-3">承認フロー</h4>
                <div className="flex items-center gap-2 flex-wrap">
                  {workflow.approvals.map((approval, index) => {
                    const status = getApproverStatus(approval, workflow);
                    return (
                    <div key={approval.id} className="contents">
                      <div className="flex items-center gap-2 bg-card px-4 py-2 rounded-lg border border-border">
                        <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-sm">
                          {approval.approver_user_id ?? '?'}
                        </div>
                        <div>
                          <div className="text-sm">{getApproverDisplay(approval)}</div>
                          <Badge
                            variant={approverStatusConfig[status].variant}
                            className="text-xs mt-1"
                          >
                            {approverStatusConfig[status].label}
                          </Badge>
                          {approval.comment ? (
                            <p className="text-xs text-muted-foreground mt-1 max-w-[180px] break-words">{approval.comment}</p>
                          ) : null}
                        </div>
                      </div>
                      {index < workflow.approvals.length - 1 && (
                        <ArrowRight className="w-4 h-4 text-muted-foreground" />
                      )}
                    </div>
                  )})}
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
        {requests.length === 0 && !loading && (
          <Card>
            <CardContent className="py-6 text-sm text-muted-foreground">申請はまだありません。</CardContent>
          </Card>
        )}
      </div>

      <Card className="bg-accent/30">
        <CardHeader>
          <CardTitle>あなたの承認待ち</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {pendingApprovals.map((pending) => (
              <div key={pending.id} className="flex items-center justify-between p-3 bg-card rounded-lg">
                <div>
                  <h4 className="mb-1">有給申請 #{pending.id}</h4>
                  <p className="text-sm text-muted-foreground">
                    {formatDate(pending.payload.start_date)} 〜 {formatDate(pending.payload.end_date)} / {pending.payload.leave_days ?? '-'}日
                  </p>
                  <p className="text-xs text-muted-foreground">
                    申請者: {pending.applicant?.name ?? `ユーザー #${pending.user_id}`} / 現在: {pending.current_step > 0 ? `${pending.current_step}段階目` : '-'}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    理由: {pending.payload.reason?.trim() ? pending.payload.reason : '-'}
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    className="text-destructive"
                    disabled={!canActorApproveNow(pending) || loading}
                    onClick={() => setDecisionDialog({ requestId: pending.id, action: 'reject' })}
                  >
                    却下
                  </Button>
                  <Button
                    size="sm"
                    disabled={!canActorApproveNow(pending) || loading}
                    onClick={() => setDecisionDialog({ requestId: pending.id, action: 'approve' })}
                  >
                    承認
                  </Button>
                </div>
              </div>
            ))}
            {pendingApprovals.length === 0 && (
              <p className="text-sm text-muted-foreground">承認待ちはありません。</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
