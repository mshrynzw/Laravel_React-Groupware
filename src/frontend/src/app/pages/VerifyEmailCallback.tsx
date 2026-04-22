import { useEffect, useState } from 'react';
import { Navigate, useLocation, useParams } from 'react-router';
import { Card } from '../components/Card';
import { useAuth } from '../auth/AuthContext';
import { apiGet } from '../lib/api';

export function VerifyEmailCallback() {
  const { id, hash } = useParams();
  const location = useLocation();
  const { refreshUser } = useAuth();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('メール認証を処理しています...');

  useEffect(() => {
    const run = async () => {
      try {
        const query = location.search;
        const response = await apiGet<{ message: string }>(`/api/verify-email/${id}/${hash}${query}`);
        setStatus('success');
        setMessage(response.message ?? 'メール認証が完了しました。');
        await refreshUser();
      } catch (e) {
        setStatus('error');
        setMessage(e instanceof Error ? e.message : 'メール認証に失敗しました。');
      }
    };
    run();
  }, [id, hash, location.search, refreshUser]);

  if (status === 'success') {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-lg p-8">
        <p className={status === 'error' ? 'text-destructive' : 'text-muted-foreground'}>{message}</p>
      </Card>
    </div>
  );
}
