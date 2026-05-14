import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Hash, Send, Users } from 'lucide-react';
import { useAuth } from '../auth/AuthContext';
import { apiGet, apiPost } from '../lib/api';
import { getEcho } from '../lib/echo';

type ChatUser = { id: number; name: string; email: string };

type ChatRoom = {
  id: number;
  name: string | null;
  type: 'direct' | 'group';
  users?: ChatUser[];
};

type ChatMessage = {
  id: number;
  body: string;
  created_at: string;
  author?: ChatUser | null;
};

const POLL_MS_WITH_REVERB = 30_000;
const POLL_MS_POLLING_ONLY = 4000;

export function Chat() {
  const { user } = useAuth();
  const [rooms, setRooms] = useState<ChatRoom[]>([]);
  const [selected, setSelected] = useState<ChatRoom | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [message, setMessage] = useState('');
  const [groupName, setGroupName] = useState('');
  const [dmUserId, setDmUserId] = useState('');
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const loadRooms = useCallback(async () => {
    try {
      const res = await apiGet<{ data: ChatRoom[] }>('/api/chat/rooms');
      setRooms(res.data);
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'ルーム取得に失敗しました。');
    }
  }, []);

  const loadMessages = useCallback(async (roomId: number) => {
    try {
      const res = await apiGet<{ data: ChatMessage[] }>(`/api/chat/rooms/${roomId}/messages?limit=80`);
      setMessages(res.data);
    } catch {
      // keep previous
    }
  }, []);

  useEffect(() => {
    void loadRooms();
  }, [loadRooms]);

  useEffect(() => {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
    }
    if (!selected) return;
    void loadMessages(selected.id);
    const pollMs = import.meta.env.VITE_REVERB_APP_KEY ? POLL_MS_WITH_REVERB : POLL_MS_POLLING_ONLY;
    pollRef.current = setInterval(() => {
      void loadMessages(selected.id);
    }, pollMs);
    return () => {
      if (pollRef.current) clearInterval(pollRef.current);
    };
  }, [selected, loadMessages]);

  useEffect(() => {
    if (!selected || !user) return;
    const echo = getEcho();
    if (!echo) return;

    const channelName = `chat.${selected.id}`;
    const channel = echo.private(channelName);
    channel.listen('.MessageSent', (payload: { data: ChatMessage }) => {
      setMessages((prev) => {
        if (prev.some((m) => m.id === payload.data.id)) return prev;
        return [...prev, payload.data].sort((a, b) => a.id - b.id);
      });
    });

    return () => {
      echo.leave(channelName);
    };
  }, [selected, user]);

  const createGroup = async () => {
    if (!groupName.trim()) return;
    setMessage('');
    try {
      await apiPost('/api/chat/rooms', { type: 'group', name: groupName.trim() });
      setGroupName('');
      await loadRooms();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '作成に失敗しました。');
    }
  };

  const openDm = async () => {
    const id = parseInt(dmUserId, 10);
    if (!id) return;
    setMessage('');
    try {
      const res = await apiPost<{ data: ChatRoom }>('/api/chat/rooms', {
        type: 'direct',
        participant_user_id: id,
      });
      setDmUserId('');
      await loadRooms();
      const room = res.data;
      setSelected(room);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'DMの開始に失敗しました。');
    }
  };

  const send = async () => {
    if (!selected || !draft.trim()) return;
    setMessage('');
    try {
      await apiPost(`/api/chat/rooms/${selected.id}/messages`, { body: draft.trim() });
      setDraft('');
      await loadMessages(selected.id);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '送信に失敗しました。');
    }
  };

  const roomLabel = (r: ChatRoom) => {
    if (r.type === 'group' && r.name) return r.name;
    if (r.users && r.users.length) {
      return r.users
        .map((u) => u.name)
        .slice(0, 3)
        .join(', ');
    }
    return `ルーム #${r.id}`;
  };

  return (
    <div className="flex flex-col gap-4 lg:flex-row lg:gap-6 lg:h-[calc(100vh-10rem)]">
      <Card className="w-full lg:w-72 flex-shrink-0 p-4 space-y-4">
        <div>
          <h3 className="mb-2 text-sm font-medium">ルーム</h3>
          <div className="space-y-1 max-h-48 overflow-y-auto">
            {rooms.map((room) => (
              <button
                key={room.id}
                type="button"
                onClick={() => setSelected(room)}
                className={`w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left text-sm transition-colors ${
                  selected?.id === room.id ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'
                }`}
              >
                <Hash className="w-4 h-4 flex-shrink-0" />
                <span className="truncate">{roomLabel(room)}</span>
              </button>
            ))}
          </div>
        </div>
        <div className="border-t border-border pt-3 space-y-2">
          <p className="text-xs text-muted-foreground">グループ作成</p>
          <input
            className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
            placeholder="チャンネル名"
            value={groupName}
            onChange={(e) => setGroupName(e.target.value)}
          />
          <Button type="button" size="sm" className="w-full" onClick={() => void createGroup()}>
            作成
          </Button>
        </div>
        <div className="border-t border-border pt-3 space-y-2">
          <p className="text-xs text-muted-foreground">DM（相手のユーザーID）</p>
          <input
            className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
            placeholder="例: 2"
            value={dmUserId}
            onChange={(e) => setDmUserId(e.target.value)}
          />
          <Button type="button" size="sm" variant="outline" className="w-full" onClick={() => void openDm()}>
            開始
          </Button>
        </div>
      </Card>

      <Card className="flex-1 flex flex-col min-h-[320px] p-4">
        {message && <p className="text-sm text-destructive mb-2">{message}</p>}
        {!selected && <p className="text-sm text-muted-foreground">ルームを選択してください。</p>}
        {selected && (
          <>
            <div className="pb-3 border-b border-border mb-3 flex items-center gap-2">
              <Hash className="w-5 h-5 text-primary" />
              <h3 className="text-lg">{roomLabel(selected)}</h3>
              <span className="text-sm text-muted-foreground ml-auto inline-flex items-center gap-1">
                <Users className="w-4 h-4" />
                {selected.users?.length ?? 0}
              </span>
            </div>

            <div className="flex-1 overflow-y-auto space-y-3 mb-3">
              {messages.map((msg) => (
                <div key={msg.id} className="flex gap-3">
                  <div className="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 text-xs">
                    {(msg.author?.name ?? '?')[0]}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-baseline gap-2 mb-0.5">
                      <span className="font-medium text-sm">{msg.author?.name ?? '不明'}</span>
                      <span className="text-xs text-muted-foreground">
                        {new Date(msg.created_at).toLocaleString('ja-JP')}
                      </span>
                    </div>
                    <p className="text-sm whitespace-pre-wrap break-words">{msg.body}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex gap-2 mt-auto">
              <input
                type="text"
                placeholder="メッセージを入力…"
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    void send();
                  }
                }}
                className="flex-1 px-3 py-2 bg-background border border-border rounded-lg text-sm"
              />
              <Button type="button" onClick={() => void send()}>
                <Send className="w-4 h-4" />
              </Button>
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
