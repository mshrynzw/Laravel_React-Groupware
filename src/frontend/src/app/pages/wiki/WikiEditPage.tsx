import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { apiGet, apiPost, apiPut } from '../../lib/api';
import { Button } from '../../components/Button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/Card';
import {
  collectWikiSubtreeIds,
  findWikiTreeNode,
  flattenWikiTree,
  type WikiTreeNode,
} from './wikiTree';

type WikiPageDetail = {
  id: number;
  slug: string;
  title: string;
  body: string;
  updated_at: string;
  parent?: { id: number; slug: string; title: string } | null;
};

type Mode = 'create' | 'edit';

export function WikiEditPage({ mode }: { mode: Mode }) {
  const navigate = useNavigate();
  const { slug: slugParam } = useParams<{ slug: string }>();
  const decodedSlug = slugParam ? decodeURIComponent(slugParam) : '';

  const [pageId, setPageId] = useState<number | null>(null);
  const [slug, setSlug] = useState('');
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [updatedAt, setUpdatedAt] = useState('');
  const [parentId, setParentId] = useState<number | ''>('');
  const [tree, setTree] = useState<WikiTreeNode[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(mode === 'edit');

  const excludedParentIds = useMemo(() => {
    if (mode !== 'edit' || pageId == null || tree.length === 0) return new Set<number>();
    const node = findWikiTreeNode(tree, pageId);
    if (!node) return new Set<number>();
    return new Set(collectWikiSubtreeIds(node));
  }, [mode, pageId, tree]);

  const parentOptions = useMemo(() => {
    const flat = flattenWikiTree(tree);
    return flat.filter((row) => !excludedParentIds.has(row.id));
  }, [tree, excludedParentIds]);

  const loadTree = useCallback(async () => {
    try {
      const res = await apiGet<{ data: WikiTreeNode[] }>('/api/wiki/pages/tree');
      setTree(res.data);
    } catch {
      setTree([]);
    }
  }, []);

  const load = useCallback(async () => {
    if (mode !== 'edit' || !decodedSlug) return;
    setLoading(true);
    try {
      const data = await apiGet<WikiPageDetail>(`/api/wiki/pages/by-slug/${encodeURIComponent(decodedSlug)}`);
      setPageId(data.id);
      setSlug(data.slug);
      setTitle(data.title);
      setBody(data.body);
      setUpdatedAt(data.updated_at);
      setParentId(data.parent?.id ?? '');
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [mode, decodedSlug]);

  useEffect(() => {
    void loadTree();
  }, [loadTree]);

  useEffect(() => {
    void load();
  }, [load]);

  const saveCreate = async () => {
    setMessage('');
    try {
      const res = await apiPost<{ data: WikiPageDetail }>('/api/wiki/pages', {
        slug: slug.trim(),
        title: title.trim(),
        body,
        parent_id: parentId === '' ? null : parentId,
      });
      navigate(`/wiki/view/${encodeURIComponent(res.data.slug)}`);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '作成に失敗しました。');
    }
  };

  const saveUpdate = async () => {
    if (pageId == null) return;
    setMessage('');
    try {
      const res = await apiPut<{ message: string; data: WikiPageDetail }>(`/api/wiki/pages/${pageId}`, {
        slug: slug.trim(),
        title: title.trim(),
        body,
        updated_at: updatedAt,
        parent_id: parentId === '' ? null : parentId,
      });
      navigate(`/wiki/view/${encodeURIComponent(res.data.slug)}`);
    } catch (e) {
      const msg = e instanceof Error ? e.message : '更新に失敗しました。';
      setMessage(msg);
      if (msg.includes('他のユーザー')) {
        await load();
      }
    }
  };

  if (loading) {
    return <p className="text-sm text-muted-foreground">読み込み中…</p>;
  }

  return (
    <div className="space-y-6 max-w-6xl">
      <div className="flex flex-wrap items-center gap-3">
        <h2 className="text-xl font-semibold">{mode === 'create' ? '新規 Wiki ページ' : 'ページを編集'}</h2>
        <Link to="/wiki" className="text-sm text-primary underline ml-auto">
          一覧へ
        </Link>
      </div>

      {message && <p className="text-sm text-destructive">{message}</p>}

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">入力</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <label className="text-xs text-muted-foreground block mb-1">親ページ（任意）</label>
              <select
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={parentId === '' ? '' : String(parentId)}
                onChange={(e) => {
                  const v = e.target.value;
                  setParentId(v === '' ? '' : Number(v));
                }}
              >
                <option value="">（ルート）</option>
                {parentOptions.map((o) => (
                  <option key={o.id} value={String(o.id)}>
                    {'\u00A0'.repeat(o.depth * 2)}
                    {o.title}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-xs text-muted-foreground block mb-1">スラッグ（英小文字・数字・ハイフン）</label>
              <input
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                value={slug}
                onChange={(e) => setSlug(e.target.value)}
                placeholder="getting-started"
              />
            </div>
            <div>
              <label className="text-xs text-muted-foreground block mb-1">タイトル</label>
              <input
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="ページタイトル"
              />
            </div>
            <div>
              <label className="text-xs text-muted-foreground block mb-1">本文（Markdown）</label>
              <textarea
                className="w-full min-h-[320px] rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                value={body}
                onChange={(e) => setBody(e.target.value)}
                spellCheck={false}
              />
            </div>
            <div className="flex gap-2">
              {mode === 'create' ? (
                <Button type="button" onClick={() => void saveCreate()}>
                  作成
                </Button>
              ) : (
                <Button type="button" onClick={() => void saveUpdate()}>
                  保存
                </Button>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">プレビュー</CardTitle>
          </CardHeader>
          <CardContent>
            <article
              className="wiki-md text-sm leading-relaxed space-y-4 border border-border rounded-lg p-4 min-h-[320px]
                [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg
                [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6
                [&_a]:text-primary [&_a]:underline [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:text-xs
                [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-3 [&_pre]:text-xs
                [&_table]:w-full [&_table]:text-xs [&_th]:border [&_th]:p-1 [&_td]:border [&_td]:p-1"
            >
              <ReactMarkdown remarkPlugins={[remarkGfm]}>{body || '*（空）*'}</ReactMarkdown>
            </article>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
