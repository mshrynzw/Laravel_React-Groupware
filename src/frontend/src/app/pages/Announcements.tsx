import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { Calendar } from 'lucide-react';
import { Button } from '../components/Button';
import { apiGet, apiPost } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

type Author = { id: number; name: string; email: string };

type AnnouncementRow = {
  id: number;
  title: string;
  body: string;
  published_at: string | null;
  author?: Author;
};

type Paginated<T> = { data: T[] };

export function Announcements() {
  const { user } = useAuth();
  const [items, setItems] = useState<AnnouncementRow[]>([]);
  const [adminItems, setAdminItems] = useState<AnnouncementRow[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [publishedAt, setPublishedAt] = useState('');

  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';

  const load = async () => {
    setLoading(true);
    try {
      const pub = await apiGet<Paginated<AnnouncementRow>>('/api/announcements?per_page=50');
      setItems(pub.data);
      if (isAdmin) {
        const adm = await apiGet<Paginated<AnnouncementRow>>('/api/admin/announcements?per_page=50');
        setAdminItems(adm.data);
      } else {
        setAdminItems([]);
      }
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, [isAdmin]);

  const createAnnouncement = async () => {
    setMessage('');
    try {
      await apiPost('/api/announcements', {
        title,
        body,
        published_at: publishedAt || null,
      });
      setTitle('');
      setBody('');
      setPublishedAt('');
      await load();
      setMessage('作成しました。');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '作成に失敗しました。');
    }
  };

  return (
    <div className="space-y-6">
      {message && <p className="text-sm text-muted-foreground">{message}</p>}
      {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

      <div className="grid gap-4">
        {items.map((announcement) => (
          <Link key={announcement.id} to={`/announcements/${announcement.id}`} className="block">
            <Card className="hover:shadow-md transition-shadow h-full">
              <CardHeader>
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="mb-2">
                      <Badge variant="default">公開</Badge>
                    </div>
                    <CardTitle>{announcement.title}</CardTitle>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-sm mb-4 line-clamp-2 text-muted-foreground">
                  {announcement.body.replace(/<[^>]+>/g, ' ').trim()}
                </p>
                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                  <div className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {announcement.published_at
                      ? new Date(announcement.published_at).toLocaleString('ja-JP')
                      : '—'}
                  </div>
                  {announcement.author && <div>投稿: {announcement.author.name}</div>}
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>

      {!loading && items.length === 0 && (
        <p className="text-sm text-muted-foreground">公開中のお知らせはありません。</p>
      )}

      {isAdmin && (
        <div className="space-y-6 border-t border-border pt-8">
          <h2 className="text-lg font-medium">管理（全件・下書き含む）</h2>
          <Card>
            <CardHeader>
              <CardTitle className="text-base">新規作成</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <label className="block text-sm">
                <span className="text-muted-foreground">タイトル</span>
                <input
                  className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                />
              </label>
              <label className="block text-sm">
                <span className="text-muted-foreground">本文</span>
                <textarea
                  className="mt-1 w-full min-h-[120px] rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                />
              </label>
              <label className="block text-sm">
                <span className="text-muted-foreground">公開日時（空なら下書き）</span>
                <input
                  type="datetime-local"
                  className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={publishedAt}
                  onChange={(e) => setPublishedAt(e.target.value)}
                />
              </label>
              <Button type="button" onClick={() => void createAnnouncement()}>
                作成
              </Button>
            </CardContent>
          </Card>

          <div className="grid gap-2">
            {adminItems.map((a) => (
              <div
                key={a.id}
                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border px-3 py-2 text-sm"
              >
                <span className="font-medium">{a.title}</span>
                <span className="text-muted-foreground text-xs">
                  {a.published_at
                    ? new Date(a.published_at).toLocaleString('ja-JP')
                    : '下書き'}
                </span>
                <Link to={`/announcements/${a.id}`} className="text-primary text-sm hover:underline">
                  開く
                </Link>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
