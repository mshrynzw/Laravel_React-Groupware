import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router';
import { FileText, FolderTree, Plus, Search } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/Card';
import { apiGet } from '../../lib/api';
import { useAuth } from '../../auth/AuthContext';
import type { WikiTreeNode } from './wikiTree';

type WikiUser = { id: number; name: string; email: string };

type WikiPageRow = {
  id: number;
  slug: string;
  title: string;
  updated_at: string;
  creator?: WikiUser | null;
  editor?: WikiUser | null;
};

type PaginatedWiki = {
  data: WikiPageRow[];
  meta?: { total?: number };
};

function WikiTreeLinks({ nodes, depth = 0 }: { nodes: WikiTreeNode[]; depth?: number }) {
  return (
    <ul className={depth === 0 ? 'space-y-0.5' : 'mt-0.5 space-y-0.5 border-l border-border pl-2 ml-1'}>
      {nodes.map((n) => (
        <li key={n.id}>
          <Link
            to={`/wiki/view/${encodeURIComponent(n.slug)}`}
            className="block rounded px-1.5 py-1 text-sm hover:bg-accent transition-colors truncate"
            title={n.title}
          >
            {n.title}
          </Link>
          {n.children.length > 0 && <WikiTreeLinks nodes={n.children} depth={depth + 1} />}
        </li>
      ))}
    </ul>
  );
}

export function WikiListPage() {
  const { user } = useAuth();
  const [items, setItems] = useState<WikiPageRow[]>([]);
  const [tree, setTree] = useState<WikiTreeNode[]>([]);
  const [treeError, setTreeError] = useState('');
  const [q, setQ] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);

  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await apiGet<{ data: WikiTreeNode[] }>('/api/wiki/pages/tree');
        if (!cancelled) {
          setTree(res.data);
          setTreeError('');
        }
      } catch (e) {
        if (!cancelled) {
          setTree([]);
          setTreeError(e instanceof Error ? e.message : 'ツリーの取得に失敗しました。');
        }
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const qs = new URLSearchParams({ per_page: '100' });
      if (q.trim()) qs.set('q', q.trim());
      const res = await apiGet<PaginatedWiki>(`/api/wiki/pages?${qs.toString()}`);
      setItems(res.data);
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [q]);

  useEffect(() => {
    const delay = q.trim() ? 280 : 0;
    const t = window.setTimeout(() => void load(), delay);
    return () => clearTimeout(t);
  }, [load, q]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="relative flex-1 min-w-[200px] max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            type="search"
            placeholder="タイトル・スラッグで検索…"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            className="w-full pl-10 pr-4 py-2 rounded-lg border border-border bg-background text-sm"
          />
        </div>
        {isAdmin && (
          <Link
            to="/wiki/new"
            className="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
          >
            <Plus className="w-4 h-4" />
            新規ページ
          </Link>
        )}
      </div>

      {message && <p className="text-sm text-destructive">{message}</p>}

      <div className="lg:grid lg:grid-cols-[minmax(0,15rem)_1fr] lg:gap-6 lg:items-start">
        <aside className="mb-6 lg:mb-0 rounded-lg border border-border bg-card p-3 lg:sticky lg:top-4">
          <div className="flex items-center gap-2 text-sm font-medium text-foreground mb-2">
            <FolderTree className="w-4 h-4 text-primary" />
            階層ナビ
          </div>
          {treeError && <p className="text-xs text-destructive mb-2">{treeError}</p>}
          {!treeError && tree.length === 0 && <p className="text-xs text-muted-foreground">ページがありません。</p>}
          {tree.length > 0 && (
            <nav className="max-h-[min(70vh,28rem)] overflow-y-auto pr-1">
              <WikiTreeLinks nodes={tree} />
            </nav>
          )}
        </aside>

        <div className="space-y-4">
          {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

          {!loading && items.length === 0 && (
            <p className="text-sm text-muted-foreground">
              ページがありません。{isAdmin && '「新規ページ」から作成できます。'}
            </p>
          )}

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((page) => (
              <Link key={page.id} to={`/wiki/view/${encodeURIComponent(page.slug)}`}>
                <Card className="h-full hover:border-primary/50 transition-colors">
                  <CardHeader className="flex flex-row items-start gap-3 space-y-0">
                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                      <FileText className="w-5 h-5 text-primary" />
                    </div>
                    <div className="min-w-0">
                      <CardTitle className="text-base leading-snug line-clamp-2">{page.title}</CardTitle>
                      <p className="text-xs text-muted-foreground mt-1 font-mono truncate">{page.slug}</p>
                    </div>
                  </CardHeader>
                  <CardContent className="text-xs text-muted-foreground space-y-1 pt-0">
                    <p>更新: {new Date(page.updated_at).toLocaleString('ja-JP')}</p>
                    <p>編集: {page.editor?.name ?? page.creator?.name ?? '—'}</p>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
