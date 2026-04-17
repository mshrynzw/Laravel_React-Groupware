import React, { useState } from 'react';
import { useNavigate } from 'react-router';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Input } from '../components/Input';

export function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    navigate('/dashboard');
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl mb-2 text-primary">癒しグループウェア</h1>
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
            <a href="#" className="text-primary hover:underline">
              パスワードを忘れた
            </a>
          </div>
          
          <Button type="submit" className="w-full" size="lg">
            ログイン
          </Button>
        </form>
        
        <div className="mt-6 text-center text-sm text-muted-foreground">
          <p>アカウントをお持ちでない方は</p>
          <a href="#" className="text-primary hover:underline">
            新規登録はこちら
          </a>
        </div>
      </Card>
    </div>
  );
}
