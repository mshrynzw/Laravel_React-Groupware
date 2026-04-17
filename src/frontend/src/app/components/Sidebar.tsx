import React from 'react';
import { NavLink } from 'react-router';
import { 
  LayoutDashboard, 
  MessageSquare, 
  Bell, 
  BookOpen, 
  Calendar, 
  CheckSquare, 
  FolderOpen, 
  GitBranch, 
  Clock, 
  DollarSign, 
  Search 
} from 'lucide-react';

const navigation = [
  { name: 'トップページ', href: '/dashboard', icon: LayoutDashboard },
  { name: 'チャット', href: '/chat', icon: MessageSquare },
  { name: 'お知らせ', href: '/announcements', icon: Bell },
  { name: 'Wiki', href: '/wiki', icon: BookOpen },
  { name: 'スケジュール', href: '/schedule', icon: Calendar },
  { name: 'タスク管理', href: '/tasks', icon: CheckSquare },
  { name: 'ファイル共有', href: '/files', icon: FolderOpen },
  { name: 'ワークフロー', href: '/workflow', icon: GitBranch },
  { name: '勤怠管理', href: '/attendance', icon: Clock },
  { name: '給与計算', href: '/payroll', icon: DollarSign },
  { name: '社内検索', href: '/search', icon: Search },
];

export function Sidebar() {
  return (
    <aside className="w-64 h-screen bg-sidebar border-r border-sidebar-border flex flex-col">
      <div className="p-6">
        <h1 className="text-xl text-primary">NatuG</h1>
      </div>
      
      <nav className="flex-1 px-4 space-y-1 overflow-y-auto">
        {navigation.map((item) => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              `flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${
                isActive
                  ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                  : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
              }`
            }
          >
            <item.icon className="w-5 h-5" />
            <span>{item.name}</span>
          </NavLink>
        ))}
      </nav>
      
      <div className="p-4 border-t border-sidebar-border">
        <div className="flex items-center gap-3 px-4 py-3">
          <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground">
            田
          </div>
          <div className="flex-1">
            <div className="text-sm">田中太郎</div>
            <div className="text-xs text-muted-foreground">開発部</div>
          </div>
        </div>
      </div>
    </aside>
  );
}
