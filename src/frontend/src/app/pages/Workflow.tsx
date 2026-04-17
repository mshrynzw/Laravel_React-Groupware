import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { Plus, ArrowRight } from 'lucide-react';

const workflows = [
  {
    id: 1,
    title: '有給休暇申請',
    applicant: '田中太郎',
    date: '2026年4月15日',
    status: 'pending',
    approvers: [
      { name: '佐藤課長', status: 'approved' },
      { name: '鈴木部長', status: 'pending' },
      { name: '山田役員', status: 'waiting' },
    ]
  },
  {
    id: 2,
    title: '経費精算（出張費）',
    applicant: '佐藤花子',
    date: '2026年4月14日',
    status: 'approved',
    approvers: [
      { name: '高橋課長', status: 'approved' },
      { name: '渡辺部長', status: 'approved' },
    ]
  },
  {
    id: 3,
    title: '備品購入申請',
    applicant: '鈴木一郎',
    date: '2026年4月13日',
    status: 'pending',
    approvers: [
      { name: '伊藤課長', status: 'pending' },
    ]
  },
  {
    id: 4,
    title: '時間外勤務申請',
    applicant: '山田美咲',
    date: '2026年4月12日',
    status: 'rejected',
    approvers: [
      { name: '中村課長', status: 'rejected' },
    ]
  },
];

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
  return (
    <div className="space-y-6">
      {/* ヘッダー */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl">ワークフロー</h2>
        <Button className="gap-2">
          <Plus className="w-4 h-4" />
          新規申請
        </Button>
      </div>

      {/* 申請一覧 */}
      <div className="space-y-4">
        {workflows.map((workflow) => (
          <Card key={workflow.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="mb-2">{workflow.title}</CardTitle>
                  <div className="flex items-center gap-3 text-sm text-muted-foreground">
                    <span>申請者: {workflow.applicant}</span>
                    <span>•</span>
                    <span>{workflow.date}</span>
                  </div>
                </div>
                <Badge variant={statusConfig[workflow.status].variant}>
                  {statusConfig[workflow.status].label}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="bg-muted/30 rounded-lg p-4">
                <h4 className="text-sm mb-3">承認フロー</h4>
                <div className="flex items-center gap-2 flex-wrap">
                  {workflow.approvers.map((approver, index) => (
                    <React.Fragment key={index}>
                      <div className="flex items-center gap-2 bg-card px-4 py-2 rounded-lg border border-border">
                        <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-sm">
                          {approver.name[0]}
                        </div>
                        <div>
                          <div className="text-sm">{approver.name}</div>
                          <Badge 
                            variant={approverStatusConfig[approver.status].variant}
                            className="text-xs mt-1"
                          >
                            {approverStatusConfig[approver.status].label}
                          </Badge>
                        </div>
                      </div>
                      {index < workflow.approvers.length - 1 && (
                        <ArrowRight className="w-4 h-4 text-muted-foreground" />
                      )}
                    </React.Fragment>
                  ))}
                </div>
              </div>

              {workflow.status === 'pending' && (
                <div className="flex gap-2 mt-4">
                  <Button variant="outline" size="sm">
                    詳細を見る
                  </Button>
                  <Button variant="outline" size="sm" className="text-destructive">
                    取り下げ
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>

      {/* 承認待ち */}
      <Card className="bg-accent/30">
        <CardHeader>
          <CardTitle>あなたの承認待ち</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            <div className="flex items-center justify-between p-3 bg-card rounded-lg">
              <div>
                <h4 className="mb-1">有給休暇申請 - 田中太郎</h4>
                <p className="text-sm text-muted-foreground">2026年4月15日</p>
              </div>
              <div className="flex gap-2">
                <Button size="sm" variant="outline" className="text-destructive">
                  却下
                </Button>
                <Button size="sm">
                  承認
                </Button>
              </div>
            </div>
            <div className="flex items-center justify-between p-3 bg-card rounded-lg">
              <div>
                <h4 className="mb-1">備品購入申請 - 鈴木一郎</h4>
                <p className="text-sm text-muted-foreground">2026年4月13日</p>
              </div>
              <div className="flex gap-2">
                <Button size="sm" variant="outline" className="text-destructive">
                  却下
                </Button>
                <Button size="sm">
                  承認
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
