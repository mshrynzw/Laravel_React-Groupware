import { useEffect, useState } from 'react';
import { Button } from '../components/Button';
import { Card, CardContent, CardHeader, CardTitle } from '../components/Card';
import { Input } from '../components/Input';
import { apiDelete, apiGet, apiPost, apiPut } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

type Group = {
  id: number;
  name: string;
  description: string | null;
};

type Paginated<T> = {
  data: T[];
  meta?: { current_page: number; last_page: number };
};

export function Groups() {
  const { user } = useAuth();
  const canManage = user?.role === 'superadmin' || user?.role === 'admin';
  const [groups, setGroups] = useState<Group[]>([]);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [editingGroupId, setEditingGroupId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [editDescription, setEditDescription] = useState('');
  const [message, setMessage] = useState('');

  const load = async (targetPage = page, targetQuery = query) => {
    const groupRes = await apiGet<Paginated<Group>>(`/api/groups?page=${targetPage}&q=${encodeURIComponent(targetQuery)}`);
    setGroups(groupRes.data);
    setPage(groupRes.meta?.current_page ?? 1);
    setLastPage(groupRes.meta?.last_page ?? 1);
  };

  useEffect(() => {
    load().catch((e) => setMessage(e.message));
  }, [page]);

  const createGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    setMessage('');
    try {
      await apiPost('/api/groups', { name, description: description || null });
      setMessage('グループを作成しました。');
      setName('');
      setDescription('');
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'グループ作成に失敗しました。');
    }
  };

  const startEdit = (group: Group) => {
    setEditingGroupId(group.id);
    setEditName(group.name);
    setEditDescription(group.description ?? '');
  };

  const updateGroup = async (groupId: number) => {
    try {
      await apiPut(`/api/groups/${groupId}`, {
        name: editName,
        description: editDescription || null,
      });
      setEditingGroupId(null);
      setMessage('グループを更新しました。');
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'グループ更新に失敗しました。');
    }
  };

  const removeGroup = async (groupId: number) => {
    if (!confirm('このグループを削除しますか？')) {
      return;
    }
    try {
      await apiDelete(`/api/groups/${groupId}`);
      setMessage('グループを削除しました。');
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'グループ削除に失敗しました。');
    }
  };

  return (
    <div className="space-y-6">
      {canManage && (
        <Card>
          <CardHeader>
            <CardTitle>グループ作成</CardTitle>
          </CardHeader>
          <CardContent>
            <form className="grid grid-cols-1 md:grid-cols-3 gap-3" onSubmit={createGroup}>
              <Input label="グループ名" value={name} onChange={(e) => setName(e.target.value)} required />
              <Input label="説明" value={description} onChange={(e) => setDescription(e.target.value)} />
              <div className="flex items-end">
                <Button type="submit" className="w-full">
                  作成
                </Button>
              </div>
            </form>
            {message && <p className="text-sm mt-3 text-muted-foreground">{message}</p>}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>グループ一覧</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="mb-4 flex gap-2">
            <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="グループ名で検索" />
            <Button type="button" variant="outline" onClick={() => load(1, query).catch((e) => setMessage(e.message))}>
              検索
            </Button>
          </div>
          <div className="space-y-3">
            {groups.map((group) => (
              <div key={group.id} className="border border-border rounded-lg p-3">
                {editingGroupId === group.id ? (
                  <div className="space-y-2">
                    <Input value={editName} onChange={(e) => setEditName(e.target.value)} />
                    <Input value={editDescription} onChange={(e) => setEditDescription(e.target.value)} />
                    <div className="flex gap-2">
                      <Button type="button" size="sm" onClick={() => updateGroup(group.id)}>
                        保存
                      </Button>
                      <Button type="button" size="sm" variant="outline" onClick={() => setEditingGroupId(null)}>
                        キャンセル
                      </Button>
                    </div>
                  </div>
                ) : (
                  <>
                    <p className="font-medium">{group.name}</p>
                    <p className="text-sm text-muted-foreground">{group.description || '説明なし'}</p>
                    {canManage && (
                      <div className="mt-2 flex gap-2">
                        <Button type="button" size="sm" variant="outline" onClick={() => startEdit(group)}>
                          編集
                        </Button>
                        <Button type="button" size="sm" variant="outline" onClick={() => removeGroup(group.id)}>
                          削除
                        </Button>
                      </div>
                    )}
                  </>
                )}
              </div>
            ))}
          </div>
          <div className="mt-4 flex gap-2">
            <Button type="button" variant="outline" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              前へ
            </Button>
            <span className="text-sm text-muted-foreground self-center">
              {page} / {lastPage}
            </span>
            <Button type="button" variant="outline" disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)}>
              次へ
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
