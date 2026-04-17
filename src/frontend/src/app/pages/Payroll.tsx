import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';

const months = [
  '2026年4月',
  '2026年3月',
  '2026年2月',
  '2026年1月',
  '2025年12月',
  '2025年11月',
];

const payrollData = {
  '2026年4月': {
    basicSalary: 300000,
    overtime: 25000,
    transportation: 10000,
    housing: 20000,
    healthInsurance: -15000,
    pension: -27450,
    employmentInsurance: -900,
    incomeTax: -8500,
    residentTax: -12000,
  }
};

export function Payroll() {
  const [selectedMonth, setSelectedMonth] = useState(0);
  const currentMonthData = payrollData['2026年4月'];

  const allowances = currentMonthData.basicSalary + currentMonthData.overtime + 
                     currentMonthData.transportation + currentMonthData.housing;
  const deductions = Math.abs(currentMonthData.healthInsurance + currentMonthData.pension + 
                     currentMonthData.employmentInsurance + currentMonthData.incomeTax + 
                     currentMonthData.residentTax);
  const netSalary = allowances - deductions;

  const previousMonth = () => {
    if (selectedMonth < months.length - 1) {
      setSelectedMonth(selectedMonth + 1);
    }
  };

  const nextMonth = () => {
    if (selectedMonth > 0) {
      setSelectedMonth(selectedMonth - 1);
    }
  };

  return (
    <div className="space-y-6">
      {/* ヘッダー */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="outline" onClick={previousMonth} disabled={selectedMonth >= months.length - 1}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <h2 className="text-2xl">{months[selectedMonth]}</h2>
          <Button variant="outline" onClick={nextMonth} disabled={selectedMonth <= 0}>
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
        <Button className="gap-2">
          <Download className="w-4 h-4" />
          給与明細をダウンロード
        </Button>
      </div>

      {/* 手取り額 */}
      <Card className="bg-gradient-to-br from-primary/10 to-secondary/10">
        <CardContent className="py-8 text-center">
          <p className="text-sm text-muted-foreground mb-2">手取り額</p>
          <p className="text-5xl mb-2">¥{netSalary.toLocaleString()}</p>
          <p className="text-sm text-muted-foreground">（支給額 ¥{allowances.toLocaleString()} - 控除額 ¥{deductions.toLocaleString()}）</p>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* 支給 */}
        <Card>
          <CardHeader>
            <CardTitle>支給</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between py-3 border-b border-border">
                <span>基本給</span>
                <span className="font-medium">¥{currentMonthData.basicSalary.toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>残業手当</span>
                <span className="font-medium">¥{currentMonthData.overtime.toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>通勤手当</span>
                <span className="font-medium">¥{currentMonthData.transportation.toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>住宅手当</span>
                <span className="font-medium">¥{currentMonthData.housing.toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 pt-4">
                <span className="font-medium">支給合計</span>
                <span className="font-medium text-lg text-primary">¥{allowances.toLocaleString()}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* 控除 */}
        <Card>
          <CardHeader>
            <CardTitle>控除</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between py-3 border-b border-border">
                <span>健康保険</span>
                <span className="font-medium text-destructive">¥{Math.abs(currentMonthData.healthInsurance).toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>厚生年金</span>
                <span className="font-medium text-destructive">¥{Math.abs(currentMonthData.pension).toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>雇用保険</span>
                <span className="font-medium text-destructive">¥{Math.abs(currentMonthData.employmentInsurance).toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>所得税</span>
                <span className="font-medium text-destructive">¥{Math.abs(currentMonthData.incomeTax).toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 border-b border-border">
                <span>住民税</span>
                <span className="font-medium text-destructive">¥{Math.abs(currentMonthData.residentTax).toLocaleString()}</span>
              </div>
              <div className="flex justify-between py-3 pt-4">
                <span className="font-medium">控除合計</span>
                <span className="font-medium text-lg text-destructive">¥{deductions.toLocaleString()}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* 年間推移 */}
      <Card>
        <CardHeader>
          <CardTitle>年間給与推移</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="h-64 flex items-end justify-between gap-2">
            {months.slice(0, 6).reverse().map((month, index) => {
              const height = 60 + (index * 5);
              return (
                <div key={month} className="flex-1 flex flex-col items-center gap-2">
                  <div className="text-xs text-muted-foreground">
                    ¥{(netSalary - (index * 5000)).toLocaleString()}
                  </div>
                  <div 
                    className="w-full bg-primary/20 hover:bg-primary/30 rounded-t-lg transition-colors cursor-pointer"
                    style={{ height: `${height}%` }}
                  ></div>
                  <div className="text-xs">{month.slice(5)}</div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
