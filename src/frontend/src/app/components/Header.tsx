import React from 'react';
import { Bell, Settings } from 'lucide-react';
import { useAuth } from '../auth/AuthContext';

interface HeaderProps {
  title: string;
}

export function Header({ title }: HeaderProps) {
  const { user, logout } = useAuth();

  return (
    <header className="h-16 bg-card border-b border-border flex items-center justify-between px-8">
      <h2 className="text-xl">{title}</h2>
      
      <div className="flex items-center gap-4">
        <span className="text-sm text-muted-foreground">{user?.name}</span>
        <button className="p-2 hover:bg-accent rounded-lg transition-colors relative">
          <Bell className="w-5 h-5" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-destructive rounded-full"></span>
        </button>
        <button className="p-2 hover:bg-accent rounded-lg transition-colors">
          <Settings className="w-5 h-5" />
        </button>
        <button
          className="px-3 py-2 text-sm rounded-lg border border-border hover:bg-accent transition-colors"
          onClick={() => logout()}
        >
          ログアウト
        </button>
      </div>
    </header>
  );
}
