const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000';

type RequestOptions = RequestInit & {
  expectJson?: boolean;
};

function getCookieValue(name: string): string | null {
  const value = document.cookie
    .split('; ')
    .find((row) => row.startsWith(`${name}=`))
    ?.split('=')[1];

  return value ? decodeURIComponent(value) : null;
}

async function request(path: string, options: RequestOptions = {}) {
  const xsrfToken = getCookieValue('XSRF-TOKEN');

  const response = await fetch(`${API_BASE_URL}${path}`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...(options.headers ?? {}),
    },
    ...options,
  });

  if (!response.ok) {
    let message = 'API request failed';
    try {
      const data = await response.json();
      message = data.message ?? message;
    } catch {
      // no-op
    }
    throw new Error(message);
  }

  if (options.expectJson === false || response.status === 204) {
    return null;
  }

  return response.json();
}

export async function getCsrfCookie() {
  await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
  });
}

export async function apiGet<T>(path: string): Promise<T> {
  return request(path, { method: 'GET' }) as Promise<T>;
}

export async function apiPost<T>(path: string, body?: unknown): Promise<T> {
  return request(path, {
    method: 'POST',
    body: body ? JSON.stringify(body) : undefined,
  }) as Promise<T>;
}

export async function apiPut<T>(path: string, body?: unknown): Promise<T> {
  return request(path, {
    method: 'PUT',
    body: body ? JSON.stringify(body) : undefined,
  }) as Promise<T>;
}

export async function apiDelete(path: string): Promise<void> {
  await request(path, {
    method: 'DELETE',
    expectJson: false,
  });
}
