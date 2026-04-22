import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Input } from '../components/Input';
import { apiPost, getCsrfCookie } from '../lib/api';

export function PasswordReset() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const initialEmail = searchParams.get('email') ?? '';

  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
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
      const response = await apiPost<{ message: string }>('/api/reset-password', {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      setMessage(response.message || 'パスワードを再設定しました。');
    } catch (e) {
      setError(e instanceof Error ? e.message : '再設定に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl mb-2 text-primary">新しいパスワードを設定</h1>
        </div>
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input label="メールアドレス" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          <Input label="新しいパスワード" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          <Input
            label="新しいパスワード（確認）"
            type="password"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            required
          />
          {!token && <p className="text-sm text-destructive">token が指定されていません。メール内リンクから開いてください。</p>}
          {message && <p className="text-sm text-primary">{message}</p>}
          {error && <p className="text-sm text-destructive">{error}</p>}
          <Button type="submit" className="w-full" disabled={submitting || !token}>
            {submitting ? '更新中...' : 'パスワードを更新'}
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
