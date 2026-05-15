import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Card, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { apiGet } from '../lib/api';
import { Megaphone, FileText, FolderOpen, ListTodo, Search as SearchIcon, Users } from 'lucide-react';

type SearchHitType = 'announcement' | 'wiki' | 'task' | 'user' | 'file';

type SearchHit = {
  type: SearchHitType;
  id: number;
  title: string;
  snippet: string;
  url: string;
};

type SearchResponse = {
  data: SearchHit[];
  meta: { total: number; current_page: number; last_page: number };
  engine?: string;
};

const typeConfig: Record<
  SearchHitType,
  { icon: typeof FileText; label: string; color: string }
> = {
  announcement: { icon: Megaphone, label: 'お知らせ', color: 'text-primary' },
  wiki: { icon: FileText, label: 'Wiki', color: 'text-emerald-600' },
  task: { icon: ListTodo, label: 'タスク', color: 'text-orange-500' },
  user: { icon: Users, label: 'ユーザー', color: 'text-purple-500' },
  file: { icon: FolderOpen, label: 'ファイル', color: 'text-sky-600' },
};

const TYPE_FILTERS: { id: SearchHitType | null; label: string }[] = [
  { id: null, label: 'すべて' },
  { id: 'announcement', label: 'お知らせ' },
  { id: 'wiki', label: 'Wiki' },
  { id: 'task', label: 'タスク' },
  { id: 'user', label: 'ユーザー' },
  { id: 'file', label: 'ファイル' },
];

export function Search() {
  const [searchQuery, setSearchQuery] = useState('');
  const [submittedQuery, setSubmittedQuery] = useState('');
  const [selectedType, setSelectedType] = useState<SearchHitType | null>(null);
  const [results, setResults] = useState<SearchHit[]>([]);
  const [total, setTotal] = useState(0);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [engine, setEngine] = useState<string | null>(null);

  const load = useCallback(async () => {
    const q = submittedQuery.trim();
    if (!q) {
      setResults([]);
      setTotal(0);
      return;
    }

    setLoading(true);
    try {
      const qs = new URLSearchParams({ q, per_page: '30' });
      if (selectedType) qs.set('type', selectedType);
      const res = await apiGet<SearchResponse>(`/api/search?${qs.toString()}`);
      setResults(res.data);
      setTotal(res.meta?.total ?? res.data.length);
      setEngine(res.engine ?? null);
      setMessage('');
    } catch (e) {
      setResults([]);
      setTotal(0);
      setMessage(e instanceof Error ? e.message : '検索に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, [submittedQuery, selectedType]);

  useEffect(() => {
    void load();
  }, [load]);

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmittedQuery(searchQuery.trim());
  };

  return (
    <div className="space-y-6">
      <Card className="bg-gradient-to-br from-primary/5 to-secondary/5">
        <CardContent className="py-8">
          <div className="max-w-2xl mx-auto">
            <h2 className="text-2xl text-center mb-6">社内検索</h2>
            <form onSubmit={onSubmit} className="relative">
              <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
              <input
                type="search"
                placeholder="お知らせ・Wiki・タスク・ユーザー・ファイルを検索…"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-12 pr-4 py-4 bg-card border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-ring text-lg"
              />
            </form>
            <p className="text-xs text-muted-foreground text-center mt-3">Enter で検索</p>
          </div>
        </CardContent>
      </Card>

      <div className="flex gap-2 flex-wrap">
        {TYPE_FILTERS.map((f) => {
          const Icon = f.id ? typeConfig[f.id].icon : null;
          return (
            <button
              key={f.label}
              type="button"
              onClick={() => setSelectedType(f.id)}
              className={`px-4 py-2 rounded-lg transition-colors flex items-center gap-2 ${
                selectedType === f.id ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/80'
              }`}
            >
              {Icon && <Icon className="w-4 h-4" />}
              {f.label}
            </button>
          );
        })}
      </div>

      {message && <p className="text-sm text-destructive">{message}</p>}
      {loading && <p className="text-sm text-muted-foreground">検索中…</p>}

      {!loading && submittedQuery && (
        <div>
          <p className="mb-4 text-sm text-muted-foreground">
            {total}件の結果（「{submittedQuery}」）
            {engine && <span className="ml-2 text-xs">· エンジン: {engine}</span>}
          </p>
          {results.length === 0 && (
            <p className="text-sm text-muted-foreground">該当する結果がありません。</p>
          )}
          <div className="space-y-4">
            {results.map((result) => {
              const cfg = typeConfig[result.type];
              const TypeIcon = cfg.icon;
              return (
                <Link key={`${result.type}-${result.id}`} to={result.url}>
                  <Card className="hover:shadow-md transition-shadow">
                    <CardContent className="p-6">
                      <div className="flex items-start gap-4">
                        <div
                          className={`w-12 h-12 rounded-lg bg-muted flex items-center justify-center flex-shrink-0 ${cfg.color}`}
                        >
                          <TypeIcon className="w-6 h-6" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-start justify-between gap-4 mb-2">
                            <h4 className="font-medium truncate">{result.title}</h4>
                            <Badge variant="default">{cfg.label}</Badge>
                          </div>
                          {result.snippet && (
                            <p className="text-sm text-muted-foreground">{result.snippet}</p>
                          )}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </Link>
              );
            })}
          </div>
        </div>
      )}

      {!submittedQuery && !loading && (
        <p className="text-sm text-muted-foreground text-center">キーワードを入力して検索してください。</p>
      )}
    </div>
  );
}