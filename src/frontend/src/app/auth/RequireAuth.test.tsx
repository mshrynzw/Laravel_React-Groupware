import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router';
import { RequireAuth } from './RequireAuth';
import * as AuthContext from './AuthContext';

vi.mock('./AuthContext', () => ({
  useAuth: vi.fn(),
}));

const mockUseAuth = vi.mocked(AuthContext.useAuth);

const baseAuth = {
  login: vi.fn(),
  logout: vi.fn(),
  refreshUser: vi.fn(),
};

describe('RequireAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('loading 中はプレースホルダーを表示する', () => {
    mockUseAuth.mockReturnValue({
      ...baseAuth,
      user: null,
      loading: true,
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route
            path="/dashboard"
            element={
              <RequireAuth>
                <div>Secret</div>
              </RequireAuth>
            }
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByText('認証状態を確認しています...')).toBeInTheDocument();
  });

  it('未ログインなら /login へ誘導する', () => {
    mockUseAuth.mockReturnValue({
      ...baseAuth,
      user: null,
      loading: false,
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route path="/login" element={<div>LoginPage</div>} />
          <Route
            path="/dashboard"
            element={
              <RequireAuth>
                <div>Secret</div>
              </RequireAuth>
            }
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByText('LoginPage')).toBeInTheDocument();
    expect(screen.queryByText('Secret')).not.toBeInTheDocument();
  });

  it('ログインかつメール認証済みなら子を表示する', () => {
    mockUseAuth.mockReturnValue({
      ...baseAuth,
      user: {
        id: 1,
        name: 'Test',
        email: 't@example.com',
        role: 'member',
        email_verified_at: '2026-01-01T00:00:00.000000Z',
      },
      loading: false,
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route
            path="/dashboard"
            element={
              <RequireAuth>
                <div>Secret</div>
              </RequireAuth>
            }
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByText('Secret')).toBeInTheDocument();
  });

  it('未認証メールのユーザーは /email-verification へ誘導する', () => {
    mockUseAuth.mockReturnValue({
      ...baseAuth,
      user: {
        id: 1,
        name: 'Test',
        email: 't@example.com',
        role: 'member',
        email_verified_at: null,
      },
      loading: false,
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route path="/email-verification" element={<div>VerifyPage</div>} />
          <Route
            path="/dashboard"
            element={
              <RequireAuth>
                <div>Secret</div>
              </RequireAuth>
            }
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByText('VerifyPage')).toBeInTheDocument();
  });

  it('未認証でも /verify-email のパスでは子を表示する', () => {
    mockUseAuth.mockReturnValue({
      ...baseAuth,
      user: {
        id: 1,
        name: 'Test',
        email: 't@example.com',
        role: 'member',
        email_verified_at: null,
      },
      loading: false,
    });

    render(
      <MemoryRouter initialEntries={['/verify-email/1/abc']}>
        <Routes>
          <Route
            path="/verify-email/:id/:hash"
            element={
              <RequireAuth>
                <div>Callback</div>
              </RequireAuth>
            }
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByText('Callback')).toBeInTheDocument();
  });
});
