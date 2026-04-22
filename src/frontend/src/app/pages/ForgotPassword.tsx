import React, { useState } from 'react';
import { Link } from 'react-router';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Input } from '../components/Input';
import { apiPost, getCsrfCookie } from '../lib/api';

export function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setMessage('');
    setError('');
    try {
      await getCsrfCookie();
      const response = await apiPost<{ message: string }>('/api/forgot-password', { email });
      setMessage(response.message || 'リセットメールを送信しました。');
    } catch (e) {
      setError(e instanceof Error ? e.message : '送信に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl mb-2 text-primary">パスワード再設定</h1>
          <p className="text-muted-foreground text-sm">登録済みメールアドレスを入力してください</p>
        </div>
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input label="メールアドレス" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          {message && <p className="text-sm text-primary">{message}</p>}
          {error && <p className="text-sm text-destructive">{error}</p>}
          <Button type="submit" className="w-full" disabled={submitting}>
            {submitting ? '送信中...' : 'リセットメールを送信'}
          </Button>
        </form>
        <div className="mt-6 text-center text-sm text-muted-foreground">
          <Link to="/login" className="text-primary hover:underline">
            ログインに戻る
          </Link>
        </div>
      </Card>
    </div>
  );
}
