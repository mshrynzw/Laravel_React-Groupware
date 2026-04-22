import { useEffect, useState } from 'react';
import { Button } from '../components/Button';
import { Card, CardContent, CardHeader, CardTitle } from '../components/Card';
import { Input } from '../components/Input';
import { apiDelete, apiGet, apiPost, apiPut } from '../lib/api';
import { useAuth } from '../auth/AuthContext';

type Group = {
  id: number;
  name: string;
};

type User = {
  id: number;
  name: string;
  email: string;
  role: string;
  primary_group_id?: number | null;
  groups: Group[];
};

type Paginated<T> = {
  data: T[];
  meta?: { current_page: number; last_page: number };
};

export function Users() {
  const { user: authUser } = useAuth();
  const canManage = authUser?.role === 'admin' || authUser?.role === 'superadmin';
  const [users, setUsers] = useState<User[]>([]);
  const [groups, setGroups] = useState<Group[]>([]);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState<'superadmin' | 'admin' | 'member'>('member');
  const [groupId, setGroupId] = useState('');
  const [primaryGroupId, setPrimaryGroupId] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [editingUserId, setEditingUserId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [editRole, setEditRole] = useState<'superadmin' | 'admin' | 'member'>('member');
  const [editGroupIds, setEditGroupIds] = useState<number[]>([]);
  const [editPrimaryGroupId, setEditPrimaryGroupId] = useState<number | null>(null);
  const [message, setMessage] = useState('');

  const load = async (targetPage = page, targetQuery = query) => {
    const [userRes, groupRes] = await Promise.all([
      apiGet<Paginated<User>>(`/api/users?page=${targetPage}&q=${encodeURIComponent(targetQuery)}`),
      apiGet<Paginated<Group>>('/api/groups'),
    ]);
    setUsers(userRes.data);
    setGroups(groupRes.data);
    setPage(userRes.meta?.current_page ?? 1);
    setLastPage(userRes.meta?.last_page ?? 1);
  };

  useEffect(() => {
    load().catch((e) => setMessage(e.message));
  }, [page]);

  const createUser = async (e: React.FormEvent) => {
    e.preventDefault();
    setMessage('');
    const payload: {
      name: string;
      email: string;
      role: 'superadmin' | 'admin' | 'member';
      group_ids?: number[];
      primary_group_id?: number | null;
    } = { name, email, role };
    if (groupId) {
      payload.group_ids = [Number(groupId)];
    }
    if (primaryGroupId) {
      payload.primary_group_id = Number(primaryGroupId);
    }
    try {
      const result = await apiPost<{ temporary_password: string }>('/api/users', payload);
      setMessage(`ユーザー作成完了（仮パスワード: ${result.temporary_password}）`);
      setName('');
      setEmail('');
      setRole('member');
      setGroupId('');
      setPrimaryGroupId('');
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'ユーザー作成に失敗しました。');
    }
  };

  const startEdit = (target: User) => {
    setEditingUserId(target.id);
    setEditName(target.name);
    setEditRole((target.role as 'superadmin' | 'admin' | 'member') ?? 'member');
    const targetGroupIds = target.groups.map((group) => group.id);
    setEditGroupIds(targetGroupIds);
    setEditPrimaryGroupId(target.primary_group_id ?? null);
  };

  const saveEdit = async (targetId: number) => {
    try {
      await apiPut(`/api/users/${targetId}`, {
        name: editName,
        role: editRole,
        group_ids: editGroupIds,
        primary_group_id: editPrimaryGroupId,
      });
      setMessage('ユーザーを更新しました。');
      setEditingUserId(null);
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'ユーザー更新に失敗しました。');
    }
  };

  const deleteUser = async (targetId: number) => {
    if (!confirm('このユーザーを削除しますか？')) {
      return;
    }
    try {
      await apiDelete(`/api/users/${targetId}`);
      setMessage('ユーザーを削除しました。');
      await load(page, query);
    } catch (e) {
      setMessage(e instanceof Error ? e.message : 'ユーザー削除に失敗しました。');
    }
  };

  const toggleEditGroup = (targetGroupId: number) => {
    setEditGroupIds((prev) => (prev.includes(targetGroupId) ? prev.filter((id) => id !== targetGroupId) : [...prev, targetGroupId]));
  };

  return (
    <div className="space-y-6">
      {canManage && (
        <Card>
          <CardHeader>
            <CardTitle>ユーザー作成</CardTitle>
          </CardHeader>
          <CardContent>
            <form className="grid grid-cols-1 md:grid-cols-6 gap-3" onSubmit={createUser}>
              <Input label="名前" value={name} onChange={(e) => setName(e.target.value)} required />
              <Input label="メール" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
              <div>
                <label className="block mb-2 text-sm text-foreground">ロール</label>
                <select className="w-full px-4 py-2 bg-input-background border border-border rounded-lg" value={role} onChange={(e) => setRole(e.target.value as 'superadmin' | 'admin' | 'member')}>
                  <option value="member">member</option>
                  <option value="admin">admin</option>
                  {authUser?.role === 'superadmin' && <option value="superadmin">superadmin</option>}
                </select>
              </div>
              <div>
                <label className="block mb-2 text-sm text-foreground">初期所属グループ</label>
                <select
                  className="w-full px-4 py-2 bg-input-background border border-border rounded-lg"
                  value={groupId}
                  onChange={(e) => setGroupId(e.target.value)}
                >
                  <option value="">未設定</option>
                  {groups.map((group) => (
                    <option key={group.id} value={group.id}>
                      {group.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block mb-2 text-sm text-foreground">主グループ</label>
                <select
                  className="w-full px-4 py-2 bg-input-background border border-border rounded-lg"
                  value={primaryGroupId}
                  onChange={(e) => setPrimaryGroupId(e.target.value)}
                >
                  <option value="">未設定</option>
                  {groupId && groups.filter((group) => group.id === Number(groupId)).map((group) => (
                    <option key={group.id} value={group.id}>
                      {group.name}
                    </option>
                  ))}
                </select>
              </div>
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
          <CardTitle>ユーザー一覧</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="mb-4 flex gap-2">
            <Input
              placeholder="名前・メールで検索"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
            <Button
              type="button"
              variant="outline"
              onClick={() => load(1, query).catch((e) => setMessage(e.message))}
            >
              検索
            </Button>
          </div>
          <div className="space-y-3">
            {users.map((user) => (
              <div key={user.id} className="border border-border rounded-lg p-3">
                {editingUserId === user.id ? (
                  <div className="space-y-2">
                    <Input value={editName} onChange={(e) => setEditName(e.target.value)} />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                      <select className="w-full px-4 py-2 bg-input-background border border-border rounded-lg" value={editRole} onChange={(e) => setEditRole(e.target.value as 'superadmin' | 'admin' | 'member')}>
                        <option value="member">member</option>
                        <option value="admin">admin</option>
                        {authUser?.role === 'superadmin' && <option value="superadmin">superadmin</option>}
                      </select>
                      <select
                        className="w-full px-4 py-2 bg-input-background border border-border rounded-lg"
                        value={editPrimaryGroupId ?? ''}
                        onChange={(e) => setEditPrimaryGroupId(e.target.value ? Number(e.target.value) : null)}
                      >
                        <option value="">主グループ未設定</option>
                        {groups
                          .filter((group) => editGroupIds.includes(group.id))
                          .map((group) => (
                            <option key={group.id} value={group.id}>
                              {group.name}
                            </option>
                          ))}
                      </select>
                      <div className="text-xs text-muted-foreground flex items-center">所属グループは下で選択</div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {groups.map((group) => (
                        <label key={group.id} className="text-xs border border-border rounded-md px-2 py-1">
                          <input
                            type="checkbox"
                            checked={editGroupIds.includes(group.id)}
                            onChange={() => toggleEditGroup(group.id)}
                            className="mr-1"
                          />
                          {group.name}
                        </label>
                      ))}
                    </div>
                    <div className="flex gap-2">
                      <Button type="button" onClick={() => saveEdit(user.id)}>
                        保存
                      </Button>
                      <Button type="button" variant="outline" onClick={() => setEditingUserId(null)}>
                        キャンセル
                      </Button>
                    </div>
                  </div>
                ) : (
                  <>
                    <p className="font-medium">{user.name}</p>
                    <p className="text-sm text-muted-foreground">{user.email}</p>
                    <p className="text-xs mt-1">role: {user.role}</p>
                    <p className="text-xs mt-1">groups: {user.groups.map((group) => group.name).join(', ') || '-'}</p>
                    <p className="text-xs mt-1">primary group: {user.groups.find((group) => group.id === user.primary_group_id)?.name ?? '-'}</p>
                    {canManage && (
                      <div className="mt-2 flex gap-2">
                        <Button type="button" size="sm" variant="outline" onClick={() => startEdit(user)}>
                          編集
                        </Button>
                        <Button type="button" size="sm" variant="outline" onClick={() => deleteUser(user.id)}>
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
