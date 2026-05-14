import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { Button } from '../components/Button';
import { FileText, Bell, TrendingUp } from 'lucide-react';
import { apiGet, apiPost } from '../lib/api';
import { todayDateInTokyo } from '../lib/attendanceDates';

type AttendanceRecordRow = {
  id: number;
  type: 'clock_in' | 'clock_out';
  recorded_at: string;
};

type Paginated<T> = { data: T[] };

export function Dashboard() {
  const navigate = useNavigate();
  const currentTime = new Date().toLocaleTimeString('ja-JP', {
    hour: '2-digit',
    minute: '2-digit',
  });

  const [todayRecords, setTodayRecords] = useState<AttendanceRecordRow[]>([]);
  const [pendingCount, setPendingCount] = useState<number | null>(null);
  const [attendanceBusy, setAttendanceBusy] = useState(false);
  const [announcements, setAnnouncements] = useState<{ id: number; title: string; published_at: string }[]>([]);

  useEffect(() => {
    const today = todayDateInTokyo();
    let cancelled = false;
    (async () => {
      try {
        const [recRes, pendingRes, annRes] = await Promise.all([
          apiGet<Paginated<AttendanceRecordRow>>(`/api/attendance/records?from=${today}&to=${today}&per_page=50`),
          apiGet<Paginated<unknown>>('/api/requests?mode=pending_approval&per_page=100'),
          apiGet<Paginated<{ id: number; title: string; published_at: string }>>('/api/announcements?per_page=3'),
        ]);
        if (!cancelled) {
          setTodayRecords(recRes.data);
          setPendingCount(pendingRes.data.length);
          setAnnouncements(annRes.data);
        }
      } catch {
        if (!cancelled) {
          setTodayRecords([]);
          setPendingCount(null);
          setAnnouncements([]);
        }
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const lastToday = todayRecords.length > 0 ? todayRecords[todayRecords.length - 1] : null;
  const canClockIn = !lastToday || lastToday.type === 'clock_out';
  const canClockOut = lastToday?.type === 'clock_in';

  const quickClockIn = async () => {
    setAttendanceBusy(true);
    try {
      await apiPost('/api/attendance/clock-in', { source: 'web' });
      const today = todayDateInTokyo();
      const recRes = await apiGet<Paginated<AttendanceRecordRow>>(`/api/attendance/records?from=${today}&to=${today}&per_page=50`);
      setTodayRecords(recRes.data);
    } finally {
      setAttendanceBusy(false);
    }
  };

  const quickClockOut = async () => {
    setAttendanceBusy(true);
    try {
      await apiPost('/api/attendance/clock-out', { source: 'web' });
      const today = todayDateInTokyo();
      const recRes = await apiGet<Paginated<AttendanceRecordRow>>(`/api/attendance/records?from=${today}&to=${today}&per_page=50`);
      setTodayRecords(recRes.data);
    } finally {
      setAttendanceBusy(false);
    }
  };

  return (
    <div className="space-y-6">
      <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 className="mb-2">今日の勤怠</h3>
            <p className="text-3xl mb-4">{currentTime}</p>
            <div className="flex flex-wrap gap-2 items-center">
              {lastToday?.type === 'clock_in' && (
                <>
                  <Badge variant="success">出勤中</Badge>
                  <span className="text-sm text-muted-foreground">
                    {new Date(lastToday.recorded_at).toLocaleTimeString('ja-JP', {
                      hour: '2-digit',
                      minute: '2-digit',
                      timeZone: 'Asia/Tokyo',
                    })}{' '}
                    出勤
                  </span>
                </>
              )}
              {lastToday?.type === 'clock_out' && (
                <Badge variant="default">本日は退勤済み</Badge>
              )}
              {!lastToday && <span className="text-sm text-muted-foreground">本日はまだ打刻がありません。</span>}
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" type="button" onClick={() => navigate('/attendance')}>
              勤怠画面へ
            </Button>
            <Button variant="outline" disabled={attendanceBusy || !canClockIn} onClick={() => void quickClockIn()}>
              出勤
            </Button>
            <Button disabled={attendanceBusy || !canClockOut} onClick={() => void quickClockOut()}>
              退勤
            </Button>
          </div>
        </div>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <FileText className="w-5 h-5 text-primary" />
              <CardTitle>未承認申請</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-3xl mb-2">{pendingCount === null ? '—' : `${pendingCount}件`}</p>
            <p className="text-sm text-muted-foreground mb-4">あなたの承認待ちの申請です。</p>
            <Button variant="outline" size="sm" className="w-full" type="button" onClick={() => navigate('/workflow')}>
              確認する
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-secondary" />
              <CardTitle>最新のお知らせ</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {announcements.length === 0 ? (
                <p className="text-sm text-muted-foreground">お知らせはありません。</p>
              ) : (
                announcements.map((a) => (
                  <div key={a.id} className="pb-3 border-b border-border last:border-0 last:pb-0">
                    <p className="text-sm mb-1">{a.title}</p>
                    <p className="text-xs text-muted-foreground">
                      {new Date(a.published_at).toLocaleString('ja-JP')}
                    </p>
                  </div>
                ))
              )}
              <Button variant="ghost" size="sm" className="w-full" type="button" onClick={() => navigate('/announcements')}>
                すべて見る
              </Button>
            </div>
          </CardContent>
        </Card>

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
