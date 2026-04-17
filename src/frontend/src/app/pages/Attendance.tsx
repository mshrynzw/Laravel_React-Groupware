import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { Clock, Calendar } from 'lucide-react';

const attendanceHistory = [
  { date: '2026年4月16日', clockIn: '09:00', clockOut: '18:30', breakTime: '1:00', totalHours: '8:30', status: 'normal' },
  { date: '2026年4月15日', clockIn: '09:15', clockOut: '19:00', breakTime: '1:00', totalHours: '8:45', status: 'overtime' },
  { date: '2026年4月14日', clockIn: '09:00', clockOut: '18:00', breakTime: '1:00', totalHours: '8:00', status: 'normal' },
  { date: '2026年4月13日', clockIn: '08:45', clockOut: '18:15', breakTime: '1:00', totalHours: '8:30', status: 'normal' },
  { date: '2026年4月12日', clockIn: '09:30', clockOut: '18:30', breakTime: '1:00', totalHours: '8:00', status: 'late' },
];

export function Attendance() {
  const [isClockedIn, setIsClockedIn] = useState(true);
  const [isOnBreak, setIsOnBreak] = useState(false);
  const currentTime = new Date().toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });

  return (
    <div className="space-y-6">
      {/* 打刻カード */}
      <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
        <CardContent className="py-8">
          <div className="text-center mb-6">
            <Clock className="w-16 h-16 mx-auto mb-4 text-primary" />
            <h2 className="text-4xl mb-2">{currentTime}</h2>
            <p className="text-muted-foreground">
              {new Date().toLocaleDateString('ja-JP', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                weekday: 'long'
              })}
            </p>
          </div>

          <div className="flex items-center justify-center gap-4 mb-6">
            {isClockedIn && (
              <>
                <Badge variant="success" className="text-base px-4 py-2">
                  出勤中
                </Badge>
                <span className="text-sm">09:00 出勤</span>
              </>
            )}
          </div>

          <div className="flex items-center justify-center gap-3">
            {!isClockedIn ? (
              <Button 
                size="lg" 
                className="px-12 py-6 text-lg"
                onClick={() => setIsClockedIn(true)}
              >
                出勤
              </Button>
            ) : (
              <>
                <Button 
                  variant={isOnBreak ? 'primary' : 'outline'}
                  size="lg"
                  onClick={() => setIsOnBreak(!isOnBreak)}
                >
                  {isOnBreak ? '休憩終了' : '休憩開始'}
                </Button>
                <Button 
                  variant="outline"
                  size="lg"
                  onClick={() => setIsClockedIn(false)}
                >
                  退勤
                </Button>
              </>
            )}
          </div>
        </CardContent>
      </Card>

      {/* 今月の統計 */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="text-center py-6">
            <p className="text-sm text-muted-foreground mb-2">総勤務時間</p>
            <p className="text-3xl text-primary">128:30</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="text-center py-6">
            <p className="text-sm text-muted-foreground mb-2">残業時間</p>
            <p className="text-3xl text-secondary">12:15</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="text-center py-6">
            <p className="text-sm text-muted-foreground mb-2">遅刻回数</p>
            <p className="text-3xl text-destructive">1</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="text-center py-6">
            <p className="text-sm text-muted-foreground mb-2">有給残日数</p>
            <p className="text-3xl">15日</p>
          </CardContent>
        </Card>
      </div>

      {/* 勤怠履歴 */}
      <Card>
        <CardHeader>
          <CardTitle>勤怠履歴</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left py-3 px-4">日付</th>
                  <th className="text-left py-3 px-4">出勤</th>
                  <th className="text-left py-3 px-4">退勤</th>
                  <th className="text-left py-3 px-4">休憩</th>
                  <th className="text-left py-3 px-4">勤務時間</th>
                  <th className="text-left py-3 px-4">ステータス</th>
                </tr>
              </thead>
              <tbody>
                {attendanceHistory.map((record, index) => (
                  <tr key={index} className="border-b border-border hover:bg-accent">
                    <td className="py-3 px-4">{record.date}</td>
                    <td className="py-3 px-4">{record.clockIn}</td>
                    <td className="py-3 px-4">{record.clockOut}</td>
                    <td className="py-3 px-4">{record.breakTime}</td>
                    <td className="py-3 px-4">{record.totalHours}</td>
                    <td className="py-3 px-4">
                      {record.status === 'normal' && <Badge variant="success">正常</Badge>}
                      {record.status === 'overtime' && <Badge variant="warning">残業</Badge>}
                      {record.status === 'late' && <Badge variant="error">遅刻</Badge>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
