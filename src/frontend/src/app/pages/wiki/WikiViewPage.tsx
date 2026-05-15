import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { ChevronRight, History, Pencil } from 'lucide-react';
import { apiGet } from '../../lib/api';
import { useAuth } from '../../auth/AuthContext';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/Card';
import { WikiDiffView } from './wikiDiff';

const btnOutline =
  'inline-flex items-center justify-center rounded-lg border border-border bg-background px-3 py-1.5 text-sm hover:bg-accent transition-colors';
const btnOutlineGap = `${btnOutline} gap-1`;

type WikiUser = { id: number; name: string; email: string };

type WikiParent = { id: number; slug: string; title: string };

type WikiPageDetail = {
  id: number;
  slug: string;
  title: string;
  body: string;
  updated_at: string;
  parent?: WikiParent | null;
  creator?: WikiUser | null;
  editor?: WikiUser | null;
};

type WikiRevisionRow = {
  id: number;
  wiki_page_id: number;
  title: string;
  body: string;
  created_at: string;
  editor?: WikiUser | null;
};

export function WikiViewPage() {
  const { slug } = useParams<{ slug: string }>();
  const { user } = useAuth();
  const [page, setPage] = useState<WikiPageDetail | null>(null);
  const [revisions, setRevisions] = useState<WikiRevisionRow[]>([]);
  const [revMessage, setRevMessage] = useState('');
  const [selectedRevIdx, setSelectedRevIdx] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);

  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';
  const decodedSlug = slug ? decodeURIComponent(slug) : '';

  useEffect(() => {
    if (!decodedSlug) return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const data = await apiGet<WikiPageDetail>(`/api/wiki/pages/by-slug/${encodeURIComponent(decodedSlug)}`);
        if (!cancelled) {
          setPage(data);
          setMessage('');
          setSelectedRevIdx(null);
          setRevMessage('');
          try {
            const revRes = await apiGet<{ data: WikiRevisionRow[] }>(`/api/wiki/pages/${data.id}/revisions`);
            if (!cancelled) {
              setRevisions(revRes.data);
            }
          } catch (e) {
            if (!cancelled) {
              setRevisions([]);
              setRevMessage(e instanceof Error ? e.message : '履歴の取得に失敗しました。');
            }
          }
        }
      } catch (e) {
        if (!cancelled) {
          setPage(null);
          setRevisions([]);
          setMessage(e instanceof Error ? e.message : '読み込みに失敗しました。');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [decodedSlug]);

  const diffPair = useMemo(() => {
    if (page == null || selectedRevIdx === null) return null;
    const rev = revisions[selectedRevIdx];
    if (!rev) return null;
    const newerTitle = selectedRevIdx === 0 ? page.title : revisions[selectedRevIdx - 1]!.title;
    const newerBody = selectedRevIdx === 0 ? page.body : revisions[selectedRevIdx - 1]!.body;
    return {
      oldTitle: rev.title,
      newTitle: newerTitle,
      oldBody: rev.body,
      newBody: newerBody,
    };
  }, [page, revisions, selectedRevIdx]);

  if (loading) {
    return <p className="text-sm text-muted-foreground">読み込み中…</p>;
  }

  if (!page) {
    return (
      <div className="space-y-4">
        {message && <p className="text-sm text-destructive">{message}</p>}
        <Link to="/wiki" className="text-sm text-primary underline">
          一覧へ戻る
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          {page.parent && (
            <p className="text-xs text-muted-foreground mb-1 flex flex-wrap items-center gap-1">
              <Link to={`/wiki/view/${encodeURIComponent(page.parent.slug)}`} className="text-primary hover:underline">
                {page.parent.title}
              </Link>
              <ChevronRight className="w-3 h-3 inline opacity-60" aria-hidden />
              <span className="font-mono">{page.slug}</span>
            </p>
          )}
          {!page.parent && <p className="text-xs text-muted-foreground font-mono mb-1">{page.slug}</p>}
          <h1 className="text-2xl font-semibold">{page.title}</h1>
          <p className="text-sm text-muted-foreground mt-2">
            更新 {new Date(page.updated_at).toLocaleString('ja-JP')} · {page.editor?.name ?? page.creator?.name ?? '—'}
          </p>
        </div>
        <div className="flex gap-2">
          <Link to="/wiki" className={btnOutline}>
            一覧
          </Link>
          {isAdmin && (
            <Link to={`/wiki/edit/${encodeURIComponent(page.slug)}`} className={btnOutlineGap}>
              <Pencil className="w-3.5 h-3.5" />
              編集
            </Link>
          )}
        </div>
      </div>

      <Card>
        <CardContent className="p-6 pt-6">
          <article
            className="wiki-md text-sm leading-relaxed space-y-4
              [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:pt-2 [&_h1]:pb-2
              [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:pt-4 [&_h2]:pb-2
              [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:pt-3
              [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6
              [&_a]:text-primary [&_a]:underline [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:text-xs
              [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-4 [&_pre]:text-xs
              [&_blockquote]:border-l-4 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:italic
              [&_table]:w-full [&_table]:text-sm [&_th]:border [&_th]:border-border [&_th]:bg-muted [&_th]:p-2 [&_td]:border [&_td]:border-border [&_td]:p-2"
          >
            <ReactMarkdown remarkPlugins={[remarkGfm]}>{page.body}</ReactMarkdown>
          </article>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center gap-2 space-y-0 pb-2">
          <History className="w-4 h-4 text-primary" />
          <CardTitle className="text-base">更新履歴と差分</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {revMessage && <p className="text-sm text-destructive">{revMessage}</p>}
          {!revMessage && revisions.length === 0 && (
            <p className="text-sm text-muted-foreground">
              まだ履歴がありません。ページを編集して保存すると、直前の内容がここに記録されます。
            </p>
          )}
          {revisions.length > 0 && (
            <>
              <ul className="space-y-1 text-sm border border-border rounded-lg divide-y divide-border max-h-48 overflow-y-auto">
                {revisions.map((r, idx) => (
                  <li key={r.id}>
                    <button
                      type="button"
                      onClick={() => setSelectedRevIdx(selectedRevIdx === idx ? null : idx)}
                      className={`w-full text-left px-3 py-2 hover:bg-accent transition-colors ${
                        selectedRevIdx === idx ? 'bg-accent' : ''
                      }`}
                    >
                      <span className="font-medium">{new Date(r.created_at).toLocaleString('ja-JP')}</span>
                      <span className="text-muted-foreground"> · {r.editor?.name ?? '—'}</span>
                      <span className="block text-xs text-muted-foreground truncate mt-0.5">保存直前のタイトル: {r.title}</span>
                    </button>
                  </li>
                ))}
              </ul>
              {diffPair && (
                <div className="space-y-3">
                  <p className="text-xs text-muted-foreground">
                    緑は追加・赤は削除（保存直前の版から、その次の版への差分）。
                  </p>
                  <div>
                    <p className="text-xs font-medium text-foreground mb-1">タイトル</p>
                    <WikiDiffView before={diffPair.oldTitle} after={diffPair.newTitle} />
                  </div>
                  <div>
                    <p className="text-xs font-medium text-foreground mb-1">本文</p>
                    <WikiDiffView before={diffPair.oldBody} after={diffPair.newBody} />
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
