import React from 'react';
import { Outlet, useLocation } from 'react-router';
import { Sidebar } from './Sidebar';
import { Header } from './Header';

const pageTitles: { [key: string]: string } = {
  '/dashboard': 'トップページ',
  '/chat': 'チャット',
  '/announcements': 'お知らせ',
  '/wiki': 'Wiki',
  '/schedule': 'スケジュール',
  '/tasks': 'タスク管理',
  '/files': 'ファイル共有',
  '/workflow': 'ワークフロー',
  '/attendance': '勤怠管理',
  '/payroll': '給与計算',
  '/search': '社内検索',
};

export function Layout() {
  const location = useLocation();
  const title = pageTitles[location.pathname] || 'ページ';

  return (
    <div className="flex h-screen bg-background">
      <Sidebar />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header title={title} />
        <main className="flex-1 overflow-y-auto p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
