import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { apiDelete, apiDownloadFile, apiGet, apiPost, apiPostFormData, getCsrfCookie } from './api';

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

  it('apiPostFormData: Content-Type を JSON にしない', async () => {
    const fd = new FormData();
    fd.append('file', new Blob(['x'], { type: 'text/plain' }), 'a.txt');
    fetchMock.mockResolvedValue({
      ok: true,
      status: 201,
      json: async () => ({ data: { id: 1 } }),
    });

    await apiPostFormData<{ data: { id: number } }>('/api/files', fd);
    const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(init.headers).not.toMatchObject({
      'Content-Type': 'application/json',
    });
    expect(init.body).toBe(fd);
  });

  it('apiDownloadFile: 成功時は GET で Blob を取り object URL を張る', async () => {
    const blob = new Blob(['hi'], { type: 'application/pdf' });
    fetchMock.mockResolvedValue(new Response(blob, { status: 200 }));

    const createObjectURL = vi.fn(() => 'blob:mock');
    const revokeObjectURL = vi.fn();
    Object.defineProperty(URL, 'createObjectURL', { value: createObjectURL, configurable: true });
    Object.defineProperty(URL, 'revokeObjectURL', { value: revokeObjectURL, configurable: true });

    const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});

    try {
      await apiDownloadFile('/api/files/1/download', 'doc.pdf');
    } finally {
      clickSpy.mockRestore();
      Reflect.deleteProperty(URL, 'createObjectURL');
      Reflect.deleteProperty(URL, 'revokeObjectURL');
    }

    expect(fetchMock).toHaveBeenCalledWith(
      `${API_BASE}/api/files/1/download`,
      expect.objectContaining({
        method: 'GET',
        credentials: 'include',
      }),
    );
    expect(createObjectURL).toHaveBeenCalled();
    expect(revokeObjectURL).toHaveBeenCalledWith('blob:mock');
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
