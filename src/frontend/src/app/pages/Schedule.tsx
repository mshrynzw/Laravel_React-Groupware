import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';

const events = [
  { id: 1, title: '全体会議', time: '10:00-11:00', date: 15, type: 'meeting' },
  { id: 2, title: 'プロジェクトレビュー', time: '14:00-15:30', date: 16, type: 'review' },
  { id: 3, title: 'クライアント打ち合わせ', time: '13:00-14:00', date: 17, type: 'meeting' },
  { id: 4, title: 'デザインレビュー', time: '15:00-16:00', date: 17, type: 'review' },
  { id: 5, title: 'チームミーティング', time: '16:00-17:00', date: 18, type: 'meeting' },
];

export function Schedule() {
  const [currentDate, setCurrentDate] = useState(new Date(2026, 3, 17)); // April 17, 2026

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

  const previousMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
  };

  const nextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
  };

  const getEventsForDate = (day: number) => {
    return events.filter(event => event.date === day);
  };

  return (
    <div className="space-y-6">
      {/* ヘッダー */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl">{monthName}</h2>
        <div className="flex gap-2">
          <Button variant="outline" onClick={previousMonth}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <Button variant="outline" onClick={nextMonth}>
            <ChevronRight className="w-4 h-4" />
          </Button>
          <Button className="gap-2">
            <Plus className="w-4 h-4" />
            予定を追加
          </Button>
        </div>
      </div>

      {/* カレンダー */}
      <Card>
        <CardContent className="p-4">
          {/* 曜日ヘッダー */}
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

          {/* カレンダーグリッド */}
          <div className="grid grid-cols-7 gap-2">
            {/* 空白セル */}
            {Array.from({ length: startingDayOfWeek }).map((_, index) => (
              <div key={`empty-${index}`} className="aspect-square"></div>
            ))}

            {/* 日付セル */}
            {Array.from({ length: daysInMonth }).map((_, index) => {
              const day = index + 1;
              const dayEvents = getEventsForDate(day);
              const isToday = day === 17;

              return (
                <div
                  key={day}
                  className={`aspect-square p-2 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer ${
                    isToday ? 'bg-primary/10 border-primary' : 'bg-card'
                  }`}
                >
                  <div className={`text-sm mb-1 ${isToday ? 'text-primary font-medium' : ''}`}>
                    {day}
                  </div>
                  <div className="space-y-1">
                    {dayEvents.slice(0, 2).map((event) => (
                      <div
                        key={event.id}
                        className="text-xs px-1 py-0.5 rounded bg-secondary/20 text-secondary-foreground truncate"
                      >
                        {event.title}
                      </div>
                    ))}
                    {dayEvents.length > 2 && (
                      <div className="text-xs text-muted-foreground">
                        +{dayEvents.length - 2}件
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* 今日の予定 */}
      <Card>
        <CardHeader>
          <CardTitle>今日の予定</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {getEventsForDate(17).map((event) => (
              <div
                key={event.id}
                className="flex items-center gap-3 p-3 rounded-lg bg-muted hover:bg-muted/80 transition-colors"
              >
                <div className="w-1 h-12 bg-primary rounded-full"></div>
                <div className="flex-1">
                  <h4 className="mb-1">{event.title}</h4>
                  <p className="text-sm text-muted-foreground">{event.time}</p>
                </div>
                <Badge variant={event.type === 'meeting' ? 'info' : 'success'}>
                  {event.type === 'meeting' ? '会議' : 'レビュー'}
                </Badge>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
