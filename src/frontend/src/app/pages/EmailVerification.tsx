import { useState } from 'react';
import { Navigate } from 'react-router';
import { Button } from '../components/Button';
import { Card } from '../components/Card';
import { useAuth } from '../auth/AuthContext';
import { apiPost } from '../lib/api';

export function EmailVerification() {
  const { user, logout } = useAuth();
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  if (user?.email_verified_at) {
    return <Navigate to="/dashboard" replace />;
  }

  const resend = async () => {
    setSubmitting(true);
    setError('');
    setMessage('');
    try {
      const response = await apiPost<{ message: string }>('/api/email/verification-notification');
      setMessage(response.message ?? '認証メールを再送しました。');
    } catch (e) {
      setError(e instanceof Error ? e.message : '認証メールの送信に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-lg p-8">
        <h1 className="text-2xl text-primary mb-4">メール認証が必要です</h1>
        <p className="text-sm text-muted-foreground mb-4">
          {user?.email} に送信された認証メールのリンクを開いてください。
        </p>
        {message && <p className="text-sm text-primary mb-3">{message}</p>}
        {error && <p className="text-sm text-destructive mb-3">{error}</p>}
        <div className="flex gap-2">
          <Button type="button" onClick={resend} disabled={submitting}>
            {submitting ? '送信中...' : '認証メールを再送'}
          </Button>
          <Button type="button" variant="outline" onClick={() => logout()}>
            ログアウト
          </Button>
        </div>
      </Card>
    </div>
  );
}
