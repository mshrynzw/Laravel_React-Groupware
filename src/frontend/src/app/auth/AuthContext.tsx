import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { apiGet, apiPost, getCsrfCookie } from '../lib/api';

type AuthUser = {
  id: number;
  name: string;
  email: string;
  role: 'superadmin' | 'admin' | 'member';
  email_verified_at?: string | null;
};

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refreshUser = async () => {
    try {
      const me = await apiGet<AuthUser>('/api/me');
      setUser(me);
    } catch {
      setUser(null);
    }
  };

  const login = async (email: string, password: string) => {
    await getCsrfCookie();
    await apiPost('/api/login', { email, password });
    await refreshUser();
  };

  const logout = async () => {
    await apiPost('/api/logout');
    setUser(null);
  };

  useEffect(() => {
    refreshUser().finally(() => setLoading(false));
  }, []);

  const value = useMemo(
    () => ({
      user,
      loading,
      login,
      logout,
      refreshUser,
    }),
    [user, loading]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
