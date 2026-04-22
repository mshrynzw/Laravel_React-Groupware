import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { apiDelete, apiGet, apiPost, getCsrfCookie } from './api';

/** `api.ts` と同じ解決式（Vitest では .env の VITE_* が効く） */
const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000';

describe('api helpers', () => {
  const fetchMock = vi.fn();

  beforeEach(() => {
    fetchMock.mockReset();
    vi.stubGlobal('fetch', fetchMock);
    document.cookie = '';
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('apiGet: 200 で JSON を返す', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ ok: true }),
    });

    await expect(apiGet('/api/me')).resolves.toEqual({ ok: true });
    expect(fetchMock).toHaveBeenCalledWith(
      `${API_BASE}/api/me`,
      expect.objectContaining({
        method: 'GET',
        credentials: 'include',
        headers: expect.objectContaining({
          Accept: 'application/json',
        }),
      }),
    );
  });

  it('apiGet: 失敗時はレスポンスの message を投げる', async () => {
    fetchMock.mockResolvedValue({
      ok: false,
      status: 422,
      json: async () => ({ message: '入力が不正です' }),
    });

    await expect(apiGet('/api/x')).rejects.toThrow('入力が不正です');
  });

  it('apiPost: ボディを JSON で送る', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ created: true }),
    });

    await apiPost('/api/login', { email: 'a@b.com', password: 'secret' });
    expect(fetchMock).toHaveBeenCalledWith(
      `${API_BASE}/api/login`,
      expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ email: 'a@b.com', password: 'secret' }),
      }),
    );
  });

  it('apiDelete: 204 のとき null を返す', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      status: 204,
    });

    await expect(apiDelete('/api/users/1')).resolves.toBeUndefined();
  });

  it('getCsrfCookie: sanctum のエンドポイントを叩く', async () => {
    fetchMock.mockResolvedValue({ ok: true } as Response);
    await getCsrfCookie();
    expect(fetchMock).toHaveBeenCalledWith(`${API_BASE}/sanctum/csrf-cookie`, {
      credentials: 'include',
    });
  });

  it('XSRF-TOKEN があるとき X-XSRF-TOKEN を付与する', async () => {
    document.cookie = 'XSRF-TOKEN=abc%3D; path=/';
    fetchMock.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    });

    await apiGet('/api/me');
    const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(init.headers).toMatchObject({
      'X-XSRF-TOKEN': 'abc=',
    });
  });
});
