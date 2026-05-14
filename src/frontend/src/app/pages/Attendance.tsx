import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { Clock } from 'lucide-react';
import { apiGet, apiPost } from '../lib/api';
import { endOfMonthYmd, formatMinutes, formatMonthInTokyo, todayDateInTokyo } from '../lib/attendanceDates';

type AttendanceRecordRow = {
  id: number;
  user_id: number;
  type: 'clock_in' | 'clock_out';
  recorded_at: string;
  source: string | null;
};

type PaginatedRecords = {
  data: AttendanceRecordRow[];
};

type SummaryPayload = {
  period: { from: string; to: string };
  days: { date: string; work_minutes: number; status: string }[];
  total_work_minutes: number;
};

export function Attendance() {
  const [month, setMonth] = useState(() => formatMonthInTokyo(new Date()));
  const [records, setRecords] = useState<AttendanceRecordRow[]>([]);
  const [summary, setSummary] = useState<SummaryPayload | null>(null);
  const [todayRecords, setTodayRecords] = useState<AttendanceRecordRow[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setMessage('');
    try {
      const from = `${month}-01`;
      const to = endOfMonthYmd(month);
      const today = todayDateInTokyo();
      const [recRes, sumRes, todayRes] = await Promise.all([
        apiGet<PaginatedRecords>(`/api/attendance/records?from=${from}&to=${to}&per_page=200`),
        apiGet<{ data: SummaryPayload }>(`/api/attendance/summary?month=${encodeURIComponent(month)}`),
        apiGet<PaginatedRecords>(`/api/attendance/records?from=${today}&to=${today}&per_page=50`),
      ]);
      setRecords(recRes.data);
      setSummary(sumRes.data);
      setTodayRecords(todayRes.data);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : '勤怠データの取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [month]);

  useEffect(() => {
    load().catch(() => setMessage('勤怠データの取得に失敗しました。'));
  }, [load]);

  const lastToday = useMemo(() => {
    if (todayRecords.length === 0) {
      return null;
    }
    return todayRecords[todayRecords.length - 1];
  }, [todayRecords]);

  const canClockIn = !lastToday || lastToday.type === 'clock_out';
  const canClockOut = lastToday?.type === 'clock_in';

  const clockIn = async () => {
    setMessage('');
    try {
      await apiPost('/api/attendance/clock-in', { source: 'web' });
      setMessage('出勤を記録しました。');
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : '出勤打刻に失敗しました。');
    }
  };

  const clockOut = async () => {
    setMessage('');
    try {
      await apiPost('/api/attendance/clock-out', { source: 'web' });
      setMessage('退勤を記録しました。');
      await load();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : '退勤打刻に失敗しました。');
    }
  };

  const currentTime = new Date().toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
  const todayLabel = new Date().toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  });

  return (
    <div className="space-y-6">
      <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
        <CardContent className="py-8">
          <div className="text-center mb-6">
            <Clock className="w-16 h-16 mx-auto mb-4 text-primary" />
            <h2 className="text-4xl mb-2">{currentTime}</h2>
            <p className="text-muted-foreground">{todayLabel}</p>
          </div>

          <div className="flex items-center justify-center gap-4 mb-6">
            {lastToday?.type === 'clock_in' && (
              <>
                <Badge variant="success" className="text-base px-4 py-2">
                  出勤中
                </Badge>
                <span className="text-sm text-muted-foreground">
                  {new Date(lastToday.recorded_at).toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })}{' '}
                  出勤
                </span>
              </>
            )}
            {lastToday?.type === 'clock_out' && (
              <Badge variant="default" className="text-base px-4 py-2">
                本日は退勤済み
              </Badge>
            )}
            {!lastToday && <span className="text-sm text-muted-foreground">本日はまだ出勤記録がありません。</span>}
          </div>

          <div className="flex items-center justify-center gap-3 flex-wrap">
            <Button size="lg" className="px-12 py-6 text-lg" onClick={clockIn} disabled={loading || !canClockIn}>
              出勤
            </Button>
            <Button size="lg" variant="outline" onClick={clockOut} disabled={loading || !canClockOut}>
              退勤
            </Button>
          </div>
          {message && <p className="text-sm text-muted-foreground text-center mt-4">{message}</p>}
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardContent className="text-center py-6">
            <p className="text-sm text-muted-foreground mb-2">今月の総勤務時間</p>
            <p className="text-3xl text-primary">
              {summary ? formatMinutes(summary.total_work_minutes) : loading ? '…' : '-'}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="py-6">
            <label className="text-sm text-muted-foreground block mb-2">表示月</label>
            <input
              className="w-full px-3 py-2 bg-input-background border border-border rounded-lg"
              type="month"
              value={month}
              onChange={(e) => setMonth(e.target.value)}
            />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>打刻一覧（{month}）</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left py-3 px-4">種別</th>
                  <th className="text-left py-3 px-4">記録日時</th>
                  <th className="text-left py-3 px-4">ソース</th>
                </tr>
              </thead>
              <tbody>
                {[...records].reverse().map((record) => (
                  <tr key={record.id} className="border-b border-border hover:bg-accent">
                    <td className="py-3 px-4">
                      {record.type === 'clock_in' ? (
                        <Badge variant="success">出勤</Badge>
                      ) : (
                        <Badge variant="default">退勤</Badge>
                      )}
                    </td>
                    <td className="py-3 px-4 text-sm">
                      {new Date(record.recorded_at).toLocaleString('ja-JP', { timeZone: 'Asia/Tokyo' })}
                    </td>
                    <td className="py-3 px-4 text-sm text-muted-foreground">{record.source ?? '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {records.length === 0 && !loading && (
            <p className="text-sm text-muted-foreground py-4">この月の打刻はありません。</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
