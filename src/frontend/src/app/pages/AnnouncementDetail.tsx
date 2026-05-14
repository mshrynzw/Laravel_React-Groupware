import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Calendar, ArrowLeft } from 'lucide-react';
import { apiGet, apiPatch, apiDelete } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

type Announcement = {
  id: number;
  title: string;
  body: string;
  published_at: string | null;
  author?: { id: number; name: string; email: string };
};

export function AnnouncementDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [row, setRow] = useState<Announcement | null>(null);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [editTitle, setEditTitle] = useState('');
  const [editBody, setEditBody] = useState('');
  const [editPublishedAt, setEditPublishedAt] = useState('');

  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';

  useEffect(() => {
    if (!id) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const data = await apiGet<Announcement>(`/api/announcements/${id}`);
        if (!cancelled) {
          setRow(data);
          setEditTitle(data.title);
          setEditBody(data.body);
          setEditPublishedAt(
            data.published_at
              ? new Date(data.published_at).toISOString().slice(0, 16)
              : ''
          );
        }
      } catch {
        if (!cancelled) {
          setMessage('お知らせを取得できませんでした。');
          setRow(null);
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [id]);

  const save = async () => {
    if (!id) return;
    setMessage('');
    try {
      await apiPatch(`/api/announcements/${id}`, {
        title: editTitle,
        body: editBody,
        published_at: editPublishedAt || null,
      });
      const data = await apiGet<Announcement>(`/api/announcements/${id}`);
      setRow(data);
      setMessage('保存しました。');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '保存に失敗しました。');
    }
  };

  const remove = async () => {
    if (!id) return;
    if (!window.confirm('このお知らせを削除しますか？')) return;
    setMessage('');
    try {
      await apiDelete(`/api/announcements/${id}`);
      navigate('/announcements');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '削除に失敗しました。');
    }
  };

  if (loading) {
    return <p className="text-muted-foreground">読み込み中…</p>;
  }

  if (!row) {
    return (
      <div className="space-y-4">
        <p className="text-destructive">{message || 'お知らせが見つかりません。'}</p>
        <Button variant="outline" type="button" onClick={() => navigate('/announcements')}>
          一覧へ
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center gap-2">
        <Link
          to="/announcements"
          className="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm hover:bg-accent"
        >
          <ArrowLeft className="w-4 h-4" />
          一覧
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{row.title}</CardTitle>
          <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground mt-2">
            <span className="inline-flex items-center gap-1">
              <Calendar className="w-3 h-3" />
              {row.published_at
                ? new Date(row.published_at).toLocaleString('ja-JP')
                : '下書き'}
            </span>
            {row.author && <span>投稿: {row.author.name}</span>}
          </div>
        </CardHeader>
        <CardContent>
          <div
            className="prose prose-sm dark:prose-invert max-w-none text-sm leading-relaxed"
            dangerouslySetInnerHTML={{ __html: row.body }}
          />
        </CardContent>
      </Card>

      {isAdmin && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">編集（管理者）</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <label className="block text-sm">
              <span className="text-muted-foreground">タイトル</span>
              <input
                className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={editTitle}
                onChange={(e) => setEditTitle(e.target.value)}
              />
            </label>
            <label className="block text-sm">
              <span className="text-muted-foreground">本文（HTML 可・サーバでサニタイズ）</span>
              <textarea
                className="mt-1 w-full min-h-[160px] rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                value={editBody}
                onChange={(e) => setEditBody(e.target.value)}
              />
            </label>
            <label className="block text-sm">
              <span className="text-muted-foreground">公開日時（空なら下書き）</span>
              <input
                type="datetime-local"
                className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={editPublishedAt}
                onChange={(e) => setEditPublishedAt(e.target.value)}
              />
            </label>
            {message && <p className="text-sm text-muted-foreground">{message}</p>}
            <div className="flex flex-wrap gap-2">
              <Button type="button" onClick={() => void save()}>
                保存
              </Button>
              <Button
                type="button"
                variant="outline"
                className="text-destructive border-destructive"
                onClick={() => void remove()}
              >
                削除
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
