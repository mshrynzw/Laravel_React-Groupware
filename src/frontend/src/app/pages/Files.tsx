import { useEffect, useState, useRef } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { FileText, Image, File, Upload, Download, Trash2 } from 'lucide-react';
import { apiDelete, apiDownloadFile, apiGet, apiPostFormData, getCsrfCookie } from '../lib/api';

type Owner = { id: number; name: string; email: string };

type FileRow = {
  id: number;
  original_name: string;
  size: number;
  mime_type: string;
  created_at: string;
  user_id: number;
  owner?: Owner;
};

type Paginated<T> = { data: T[] };

function formatBytes(n: number): string {
  if (n < 1024) return `${n} B`;
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
  return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}

function iconForMime(m: string) {
  if (m.startsWith('image/')) return <Image className="w-8 h-8 text-secondary" />;
  if (m === 'application/pdf') return <FileText className="w-8 h-8 text-primary" />;
  return <File className="w-8 h-8 text-muted-foreground" />;
}

export function Files() {
  const [rows, setRows] = useState<FileRow[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  const load = async () => {
    setLoading(true);
    try {
      const res = await apiGet<Paginated<FileRow>>('/api/files?per_page=100');
      setRows(res.data);
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '一覧の取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const onPickFile = async (file: File | null) => {
    if (!file) return;
    setUploading(true);
    setMessage('');
    try {
      await getCsrfCookie();
      const fd = new FormData();
      fd.append('file', file);
      await apiPostFormData<{ data: FileRow }>('/api/files', fd);
      await load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'アップロードに失敗しました。');
    } finally {
      setUploading(false);
      if (inputRef.current) inputRef.current.value = '';
    }
  };

  const download = async (f: FileRow) => {
    setMessage('');
    try {
      await apiDownloadFile(`/api/files/${f.id}/download`, f.original_name);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'ダウンロードに失敗しました。');
    }
  };

  const remove = async (f: FileRow) => {
    if (!window.confirm(`「${f.original_name}」を削除しますか？`)) return;
    setMessage('');
    try {
      await apiDelete(`/api/files/${f.id}`);
      await load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '削除に失敗しました。');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl">ファイル共有</h2>
        <div>
          <input
            ref={inputRef}
            type="file"
            className="hidden"
            accept=".pdf,.png,.jpg,.jpeg,.gif,.txt,.zip,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
            onChange={(e) => void onPickFile(e.target.files?.[0] ?? null)}
          />
          <Button className="gap-2" type="button" disabled={uploading} onClick={() => inputRef.current?.click()}>
            <Upload className="w-4 h-4" />
            {uploading ? 'アップロード中…' : 'ファイルをアップロード'}
          </Button>
        </div>
      </div>

      {message && <p className="text-sm text-destructive">{message}</p>}
      {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

      <Card>
        <CardHeader>
          <CardTitle>ファイル一覧</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          {rows.length === 0 && !loading ? (
            <p className="p-6 text-sm text-muted-foreground">ファイルがありません。</p>
          ) : (
            <div className="divide-y divide-border">
              {rows.map((file) => (
                <div
                  key={file.id}
                  className="flex items-center gap-4 p-4 hover:bg-accent transition-colors"
                >
                  <div className="w-12 h-12 rounded-lg bg-muted flex items-center justify-center flex-shrink-0">
                    {iconForMime(file.mime_type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <h4 className="mb-1 truncate font-medium">{file.original_name}</h4>
                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                      <span>{formatBytes(file.size)}</span>
                      <span>•</span>
                      <span>{new Date(file.created_at).toLocaleString('ja-JP')}</span>
                      {file.owner && (
                        <>
                          <span>•</span>
                          <span>{file.owner.name}</span>
                        </>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-1 flex-shrink-0">
                    <button
                      type="button"
                      className="p-2 hover:bg-muted rounded-lg transition-colors"
                      title="ダウンロード"
                      onClick={() => void download(file)}
                    >
                      <Download className="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      className="p-2 hover:bg-muted rounded-lg transition-colors text-destructive"
                      title="削除"
                      onClick={() => void remove(file)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
