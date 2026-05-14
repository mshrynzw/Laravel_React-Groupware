import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { API_BASE_URL, broadcastAuthHeaders } from './api';

let echoInstance: Echo | null = null;

function isReverbConfigured(): boolean {
  return Boolean(import.meta.env.VITE_REVERB_APP_KEY);
}

/** ログイン済みセッションで Echo を初期化（未設定時は何もしない） */
export function initEcho(): Echo | null {
  if (!isReverbConfigured()) {
    return null;
  }

  disconnectEcho();

  window.Pusher = Pusher;

  const scheme = (import.meta.env.VITE_REVERB_SCHEME as string | undefined) ?? 'http';
  const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
  const host = (import.meta.env.VITE_REVERB_HOST as string | undefined) ?? '127.0.0.1';

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY as string,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: `${API_BASE_URL}/broadcasting/auth`,
    auth: {
      headers: broadcastAuthHeaders(),
      withCredentials: true,
    },
  });

  return echoInstance;
}

export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}

export function getEcho(): Echo | null {
  return echoInstance;
}

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}
