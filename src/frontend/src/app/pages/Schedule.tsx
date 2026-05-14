import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { apiGet, apiPost } from '../lib/api';
import { formatWeekTitle, monthRangeIso, weekRangeIso } from '../lib/scheduleCalendar';

type ScheduleRow = {
  id: number;
  title: string;
  description: string | null;
  start_at: string;
  end_at: string;
  all_day: boolean;
};

type SchedulesResponse = { data: ScheduleRow[] };

type ViewMode = 'month' | 'week';

function formatTimeRange(s: ScheduleRow): string {
  const a = new Date(s.start_at);
  const b = new Date(s.end_at);
  return `${a.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })}–${b.toLocaleTimeString('ja-JP', {
    hour: '2-digit',
    minute: '2-digit',
  })}`;
}

function getEventsForLocalYmd(rows: ScheduleRow[], y: number, monthIndex: number, day: number): ScheduleRow[] {
  return rows.filter((ev) => {
    const d = new Date(ev.start_at);
    return d.getFullYear() === y && d.getMonth() === monthIndex && d.getDate() === day;
  });
}

function isSameLocalDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

export function Schedule() {
  const [viewMode, setViewMode] = useState<ViewMode>('month');
  const [currentDate, setCurrentDate] = useState(() => new Date());
  const [rows, setRows] = useState<ScheduleRow[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [startAt, setStartAt] = useState('');
  const [endAt, setEndAt] = useState('');

  const { from, to } = useMemo(
    () => (viewMode === 'month' ? monthRangeIso(currentDate) : weekRangeIso(currentDate)),
    [currentDate, viewMode]
  );

  const weekDayDates = useMemo(() => {
    const { from: wFrom } = weekRangeIso(currentDate);
    const [y, mo, da] = wFrom.split('-').map((v) => parseInt(v, 10));
    return Array.from({ length: 7 }, (_, i) => new Date(y, mo - 1, da + i));
  }, [currentDate]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiGet<SchedulesResponse>(
        `/api/schedules?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`
      );
      setRows(res.data);
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [from, to]);

  useEffect(() => {
    void load();
  }, [load]);

  const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    return { daysInMonth, startingDayOfWeek };
  };

  const { daysInMonth, startingDayOfWeek } = getDaysInMonth(currentDate);
  const monthName = currentDate.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' });
  const rangeTitle = viewMode === 'month' ? monthName : formatWeekTitle(currentDate);

  const previousRange = () => {
    if (viewMode === 'month') {
      setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
    } else {
      const n = new Date(currentDate);
      n.setDate(n.getDate() - 7);
      setCurrentDate(n);
    }
  };

  const nextRange = () => {
    if (viewMode === 'month') {
      setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
    } else {
      const n = new Date(currentDate);
      n.setDate(n.getDate() + 7);
      setCurrentDate(n);
    }
  };

  const today = new Date();
  const todayEvents = getEventsForLocalYmd(rows, today.getFullYear(), today.getMonth(), today.getDate());

  const createSchedule = async () => {
    if (!title.trim() || !startAt || !endAt) return;
    setMessage('');
    try {
      await apiPost('/api/schedules', {
        title: title.trim(),
        start_at: new Date(startAt).toISOString(),
        end_at: new Date(endAt).toISOString(),
        all_day: false,
      });
      setTitle('');
      setStartAt('');
      setEndAt('');
      await load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '作成に失敗しました。');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl">{rangeTitle}</h2>
        <div className="flex flex-wrap items-center gap-2">
          <div className="inline-flex rounded-md border border-border p-0.5 bg-muted/40">
            <Button
              type="button"
              size="sm"
              variant={viewMode === 'month' ? 'default' : 'ghost'}
              className="h-8 px-3"
              onClick={() => setViewMode('month')}
            >
              月
            </Button>
            <Button
              type="button"
              size="sm"
              variant={viewMode === 'week' ? 'default' : 'ghost'}
              className="h-8 px-3"
              onClick={() => setViewMode('week')}
            >
              週
            </Button>
          </div>
          <Button variant="outline" type="button" onClick={previousRange}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <Button variant="outline" type="button" onClick={nextRange}>
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
      </div>

      {message && <p className="text-sm text-destructive">{message}</p>}
      {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

      <Card className="p-4 space-y-3">
        <h3 className="text-sm font-medium flex items-center gap-2">
          <Plus className="w-4 h-4" />
          予定を追加
        </h3>
        <input
          className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          placeholder="タイトル"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
        />
        <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
          <label className="text-xs text-muted-foreground">
            開始
            <input
              type="datetime-local"
              className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={startAt}
              onChange={(e) => setStartAt(e.target.value)}
            />
          </label>
          <label className="text-xs text-muted-foreground">
            終了
            <input
              type="datetime-local"
              className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={endAt}
              onChange={(e) => setEndAt(e.target.value)}
            />
          </label>
        </div>
        <Button type="button" onClick={() => void createSchedule()}>
          作成
        </Button>
      </Card>

      {viewMode === 'month' && (
        <Card>
          <CardContent className="p-4">
            <div className="grid grid-cols-7 gap-2 mb-2">
              {['日', '月', '火', '水', '木', '金', '土'].map((day, index) => (
                <div
                  key={day}
                  className={`text-center py-2 text-sm font-medium ${
                    index === 0 ? 'text-destructive' : index === 6 ? 'text-secondary' : ''
                  }`}
                >
                  {day}
                </div>
              ))}
            </div>

            <div className="grid grid-cols-7 gap-2">
              {Array.from({ length: startingDayOfWeek }).map((_, index) => (
                <div key={`empty-${index}`} className="aspect-square" />
              ))}

              {Array.from({ length: daysInMonth }).map((_, index) => {
                const day = index + 1;
                const dayEvents = getEventsForLocalYmd(
                  rows,
                  currentDate.getFullYear(),
                  currentDate.getMonth(),
                  day
                );
                const isToday =
                  today.getFullYear() === currentDate.getFullYear() &&
                  today.getMonth() === currentDate.getMonth() &&
                  today.getDate() === day;

                return (
                  <div
                    key={day}
                    className={`aspect-square p-2 rounded-lg border border-border hover:bg-accent transition-colors ${
                      isToday ? 'bg-primary/10 border-primary' : 'bg-card'
                    }`}
                  >
                    <div className={`text-sm mb-1 ${isToday ? 'text-primary font-medium' : ''}`}>{day}</div>
                    <div className="space-y-1">
                      {dayEvents.slice(0, 2).map((event) => (
                        <div
                          key={event.id}
                          className="text-xs px-1 py-0.5 rounded bg-secondary/20 text-secondary-foreground truncate"
                          title={event.title}
                        >
                          {event.title}
                        </div>
                      ))}
                      {dayEvents.length > 2 && (
                        <div className="text-xs text-muted-foreground">+{dayEvents.length - 2}件</div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      )}

      {viewMode === 'week' && (
        <Card>
          <CardContent className="p-4">
            <div className="grid grid-cols-7 gap-2 mb-2">
              {['日', '月', '火', '水', '木', '金', '土'].map((day, index) => (
                <div
                  key={day}
                  className={`text-center py-2 text-sm font-medium ${
                    index === 0 ? 'text-destructive' : index === 6 ? 'text-secondary' : ''
                  }`}
                >
                  {day}
                </div>
              ))}
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-7 gap-2">
              {weekDayDates.map((cellDate) => {
                const dayEvents = getEventsForLocalYmd(
                  rows,
                  cellDate.getFullYear(),
                  cellDate.getMonth(),
                  cellDate.getDate()
                );
                const isToday = isSameLocalDay(cellDate, today);
                return (
                  <div
                    key={`${cellDate.getFullYear()}-${cellDate.getMonth()}-${cellDate.getDate()}`}
                    className={`min-h-[200px] p-2 rounded-lg border border-border hover:bg-accent/50 transition-colors ${
                      isToday ? 'bg-primary/10 border-primary' : 'bg-card'
                    }`}
                  >
                    <div className={`text-sm font-medium mb-2 ${isToday ? 'text-primary' : ''}`}>
                      {cellDate.toLocaleDateString('ja-JP', { month: 'numeric', day: 'numeric' })}
                    </div>
                    <div className="space-y-1.5">
                      {dayEvents.length === 0 && (
                        <p className="text-xs text-muted-foreground">予定なし</p>
                      )}
                      {dayEvents.map((event) => (
                        <div
                          key={event.id}
                          className="text-xs px-1.5 py-1 rounded bg-secondary/20 text-secondary-foreground"
                          title={event.title}
                        >
                          <div className="font-medium truncate">{event.title}</div>
                          <div className="text-[10px] text-muted-foreground mt-0.5">{formatTimeRange(event)}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>本日の予定</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {todayEvents.length === 0 && <p className="text-sm text-muted-foreground">本日の予定はありません。</p>}
            {todayEvents.map((event) => (
              <div
                key={event.id}
                className="flex items-center gap-3 p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors"
              >
                <div className="w-1 h-12 bg-primary rounded-full" />
                <div className="flex-1">
                  <h4 className="mb-1">{event.title}</h4>
                  <p className="text-sm text-muted-foreground">{formatTimeRange(event)}</p>
                </div>
                <Badge variant="default">予定</Badge>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
