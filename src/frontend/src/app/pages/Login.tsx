import React, { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Input } from '../components/Input';
import { useAuth } from '../auth/AuthContext';

export function Login() {
  const navigate = useNavigate();
  const { user, login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (e) {
      setError(e instanceof Error ? e.message : 'ログインに失敗しました。');
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
          <h1 className="text-2xl mb-2 text-primary">NatuG</h1>
          <p className="text-muted-foreground">
            心地よい働き方を、あなたに
          </p>
        </div>
        
        <form onSubmit={handleLogin} className="space-y-4">
          <Input
            label="メールアドレス"
            type="email"
            placeholder="example@company.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
          
          <Input
            label="パスワード"
            type="password"
            placeholder="••••••••"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
          
          <div className="flex items-center justify-between text-sm">
            <label className="flex items-center gap-2">
              <input type="checkbox" className="rounded" />
              <span>ログイン状態を保持</span>
            </label>
            <Link to="/forgot-password" className="text-primary hover:underline">
              パスワードを忘れた
            </Link>
          </div>

          {error && (
            <p className="text-sm text-destructive">{error}</p>
          )}
          
          <Button type="submit" className="w-full" size="lg" disabled={submitting}>
            {submitting ? 'ログイン中...' : 'ログイン'}
          </Button>
        </form>
        
        <div className="mt-6 text-center text-sm text-muted-foreground">
          <p>アカウントをお持ちでない方は</p>
          <Link to="/register" className="text-primary hover:underline">
            新規登録はこちら
          </Link>
        </div>
      </Card>
    </div>
  );
}
