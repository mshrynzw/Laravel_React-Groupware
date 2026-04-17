import React from 'react';

interface BadgeProps {
  children: React.ReactNode;
  variant?: 'default' | 'success' | 'warning' | 'error' | 'info';
  className?: string;
}

export function Badge({ children, variant = 'default', className = '' }: BadgeProps) {
  const variants = {
    default: 'bg-muted text-muted-foreground',
    success: 'bg-primary/20 text-primary',
    warning: 'bg-yellow-100 text-yellow-800',
    error: 'bg-destructive/20 text-destructive',
    info: 'bg-secondary/20 text-secondary',
  };
  
  return (
    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs ${variants[variant]} ${className}`}>
      {children}
    </span>
  );
}
