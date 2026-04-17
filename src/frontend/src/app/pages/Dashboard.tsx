import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { Button } from '../components/Button';
import { Clock, FileText, Bell, TrendingUp } from 'lucide-react';

export function Dashboard() {
  const currentTime = new Date().toLocaleTimeString('ja-JP', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });

  return (
    <div className="space-y-6">
      {/* 勤怠カード */}
      <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="mb-2">今日の勤怠</h3>
            <p className="text-3xl mb-4">{currentTime}</p>
            <div className="flex gap-2">
              <Badge variant="success">出勤中</Badge>
              <span className="text-sm text-muted-foreground">09:00 出勤</span>
            </div>
          </div>
          <div className="flex gap-2">
            <Button variant="outline">休憩</Button>
            <Button>退勤</Button>
          </div>
        </div>
      </Card>

      {/* ウィジェットグリッド */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {/* 未承認申請 */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <FileText className="w-5 h-5 text-primary" />
              <CardTitle>未承認申請</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-3xl mb-2">3件</p>
            <p className="text-sm text-muted-foreground mb-4">
              承認待ちの申請があります
            </p>
            <Button variant="outline" size="sm" className="w-full">
              確認する
            </Button>
          </CardContent>
        </Card>

        {/* 最新のお知らせ */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-secondary" />
              <CardTitle>最新のお知らせ</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="pb-3 border-b border-border">
                <p className="text-sm mb-1">全社会議のお知らせ</p>
                <p className="text-xs text-muted-foreground">2時間前</p>
              </div>
              <div className="pb-3 border-b border-border">
                <p className="text-sm mb-1">システムメンテナンス</p>
                <p className="text-xs text-muted-foreground">1日前</p>
              </div>
              <Button variant="ghost" size="sm" className="w-full">
                すべて見る
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* 今週のタスク */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <TrendingUp className="w-5 h-5 text-primary" />
              <CardTitle>今週のタスク</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <div className="mb-4">
              <div className="flex justify-between mb-2">
                <span className="text-sm">完了率</span>
                <span className="text-sm">7/10</span>
              </div>
              <div className="w-full bg-muted rounded-full h-2">
                <div className="bg-primary h-2 rounded-full" style={{ width: '70%' }}></div>
              </div>
            </div>
            <Button variant="outline" size="sm" className="w-full">
              タスクを見る
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* 最近のアクティビティ */}
      <Card>
        <CardHeader>
          <CardTitle>最近のアクティビティ</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[
              { user: '佐藤花子', action: 'プロジェクト資料を更新しました', time: '10分前' },
              { user: '鈴木一郎', action: 'タスク「デザインレビュー」を完了しました', time: '30分前' },
              { user: '山田美咲', action: 'チャットでメッセージを送信しました', time: '1時間前' },
              { user: '高橋健太', action: 'ワークフローを承認しました', time: '2時間前' },
            ].map((activity, index) => (
              <div key={index} className="flex items-start gap-3 pb-4 border-b border-border last:border-0 last:pb-0">
                <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                  {activity.user[0]}
                </div>
                <div className="flex-1">
                  <p className="text-sm">
                    <span className="font-medium">{activity.user}</span>
                    <span className="text-muted-foreground"> {activity.action}</span>
                  </p>
                  <p className="text-xs text-muted-foreground mt-1">{activity.time}</p>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
