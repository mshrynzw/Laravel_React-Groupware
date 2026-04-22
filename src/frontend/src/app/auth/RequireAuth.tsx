import type { ReactElement } from 'react';
import { Navigate, useLocation } from 'react-router';
import { useAuth } from './AuthContext';

export function RequireAuth({ children }: { children: ReactElement }) {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) {
    return <div className="p-8">認証状態を確認しています...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (!user.email_verified_at && location.pathname !== '/email-verification' && !location.pathname.startsWith('/verify-email/')) {
    return <Navigate to="/email-verification" replace />;
  }

  return children;
}
