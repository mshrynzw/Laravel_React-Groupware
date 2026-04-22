import React, { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Input } from '../components/Input';
import { apiPost, getCsrfCookie } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

export function Register() {
  const navigate = useNavigate();
  const { user, refreshUser } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await getCsrfCookie();
      await apiPost('/api/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      await refreshUser();
      navigate('/dashboard');
    } catch (e) {
      setError(e instanceof Error ? e.message : '新規登録に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  if (user) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl mb-2 text-primary">NatuG 新規登録</h1>
        </div>

        <form onSubmit={handleRegister} className="space-y-4">
          <Input label="名前" value={name} onChange={(e) => setName(e.target.value)} required />
          <Input label="メールアドレス" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          <Input label="パスワード" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          <Input
            label="パスワード（確認）"
            type="password"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            required
          />

          {error && <p className="text-sm text-destructive">{error}</p>}

          <Button type="submit" className="w-full" size="lg" disabled={submitting}>
            {submitting ? '登録中...' : '登録する'}
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
