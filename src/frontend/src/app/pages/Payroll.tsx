import { useCallback, useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../components/Card';
import { Button } from '../components/Button';
import { apiDownloadFile, apiGet, apiPost } from '../lib/api';
import { useAuth } from '../auth/AuthContext';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';

type Breakdown = {
  schema_version: number;
  base_salary: number;
  allowances: { name: string; amount: number }[];
  deductions: { name: string; amount: number }[];
  tax_detail?: {
    taxable_income?: number;
    income_tax?: number;
    resident_tax?: number;
    note?: string;
  };
  attendance: { work_days: number; absence_days: number; total_work_minutes?: number };
};

type PayrollSlipRow = {
  id: number;
  user_id: number;
  gross_amount: string;
  net_amount: string;
  breakdown: Breakdown;
  run?: { period_year: number; period_month: number; status: string };
  user?: { id: number; name: string };
};

type PaginatedSlips = {
  data: PayrollSlipRow[];
};

function periodLabel(year: number, month: number) {
  return `${year}年${month}月`;
}

export function Payroll() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';

  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [slip, setSlip] = useState<PayrollSlipRow | null>(null);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [running, setRunning] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiGet<PaginatedSlips>(
        `/api/payroll/slips?year=${year}&month=${month}&per_page=1`
      );
      const row = res.data[0] ?? null;
      setSlip(row);
      setMessage(row ? '' : 'この月の給与明細はまだありません。');
    } catch (e) {
      setSlip(null);
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [year, month]);

  useEffect(() => {
    void load();
  }, [load]);

  const shiftMonth = (delta: number) => {
    const d = new Date(year, month - 1 + delta, 1);
    setYear(d.getFullYear());
    setMonth(d.getMonth() + 1);
  };

  const runPayroll = async () => {
    setRunning(true);
    setMessage('');
    try {
      await apiPost('/api/payroll/runs', { year, month });
      await load();
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '給与計算に失敗しました。');
    } finally {
      setRunning(false);
    }
  };

  const breakdown = slip?.breakdown;
  const gross = slip ? Number(slip.gross_amount) : 0;
  const net = slip ? Number(slip.net_amount) : 0;
  const deductionTotal = gross - net;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button type="button" variant="outline" onClick={() => shiftMonth(-1)}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <h2 className="text-2xl">{periodLabel(year, month)}</h2>
          <Button type="button" variant="outline" onClick={() => shiftMonth(1)}>
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
        <div className="flex gap-2">
          {slip && (
            <Button
              type="button"
              variant="outline"
              className="gap-2"
              onClick={() =>
                void apiDownloadFile(
                  `/api/payroll/slips/${slip.id}/download`,
                  `payroll-${year}-${String(month).padStart(2, '0')}.pdf`
                )
              }
            >
              <Download className="w-4 h-4" />
              給与明細 PDF
            </Button>
          )}
          {isAdmin && (
            <Button type="button" onClick={() => void runPayroll()} disabled={running}>
              {running ? '計算中…' : 'この月の給与を計算'}
            </Button>
          )}
        </div>
      </div>

      {message && !loading && <p className="text-sm text-muted-foreground">{message}</p>}
      {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

      {slip && breakdown && (
        <>
          <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
            <CardContent className="py-8 text-center">
              <p className="text-sm text-muted-foreground mb-2">手取り額</p>
              <p className="text-5xl mb-2">¥{net.toLocaleString()}</p>
              <p className="text-sm text-muted-foreground">
                支給額 ¥{gross.toLocaleString()} − 控除額 ¥{deductionTotal.toLocaleString()}
              </p>
            </CardContent>
          </Card>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>支給</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between py-3 border-b border-border">
                  <span>基本給</span>
                  <span className="font-medium">¥{breakdown.base_salary.toLocaleString()}</span>
                </div>
                {breakdown.allowances.map((a) => (
                  <div key={a.name} className="flex justify-between py-3 border-b border-border">
                    <span>{a.name}</span>
                    <span className="font-medium">¥{a.amount.toLocaleString()}</span>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>控除</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {breakdown.deductions.map((d) => (
                  <div key={d.name} className="flex justify-between py-3 border-b border-border">
                    <span>{d.name}</span>
                    <span className="font-medium text-destructive">¥{d.amount.toLocaleString()}</span>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>

          {breakdown.schema_version >= 2 && breakdown.tax_detail && (
            <Card>
              <CardHeader>
                <CardTitle>税金詳細</CardTitle>
              </CardHeader>
              <CardContent className="text-sm text-muted-foreground space-y-1">
                {breakdown.tax_detail.taxable_income != null && (
                  <p>課税所得: ¥{breakdown.tax_detail.taxable_income.toLocaleString()}</p>
                )}
                {breakdown.tax_detail.note && <p>{breakdown.tax_detail.note}</p>}
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle>勤怠サマリ（計算時点）</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground space-y-1">
              <p>出勤日数: {breakdown.attendance.work_days} 日</p>
              <p>欠勤日数: {breakdown.attendance.absence_days} 日</p>
              {breakdown.attendance.total_work_minutes != null && (
                <p>
                  総労働時間: {Math.floor(breakdown.attendance.total_work_minutes / 60)} 時間
                </p>
              )}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}
