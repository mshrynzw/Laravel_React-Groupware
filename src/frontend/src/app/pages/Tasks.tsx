import React, { useCallback, useEffect, useState } from 'react';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { Plus } from 'lucide-react';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { apiGet, apiPatch, apiPost } from '../lib/api';

type TaskStatus = 'todo' | 'in_progress' | 'done';

type ApiTask = {
  id: number;
  title: string;
  description: string | null;
  status: TaskStatus;
  position: number;
  assignee?: { id: number; name: string; email: string } | null;
};

type Paginated<T> = { data: T[] };

type ColumnDef = { id: string; title: string; status: TaskStatus };

const COLUMNS: ColumnDef[] = [
  { id: 'todo', title: '未着手', status: 'todo' },
  { id: 'in_progress', title: '進行中', status: 'in_progress' },
  { id: 'done', title: '完了', status: 'done' },
];

function TaskCard({ task }: { task: ApiTask }) {
  const [{ isDragging }, drag] = useDrag(() => ({
    type: 'TASK',
    item: { id: task.id },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  }));

  const assigneeLabel = task.assignee?.name ?? '未割当';

  return (
    <div
      ref={drag}
      className={`bg-card border border-border rounded-lg p-3 cursor-move hover:shadow-md transition-shadow ${
        isDragging ? 'opacity-50' : ''
      }`}
    >
      <h4 className="text-sm mb-1">{task.title}</h4>
      {task.description && <p className="text-xs text-muted-foreground mb-2 line-clamp-2">{task.description}</p>}
      <div className="flex items-center justify-between">
        <Badge variant="default" className="text-xs">
          {assigneeLabel}
        </Badge>
      </div>
    </div>
  );
}

function TaskColumn({
  column,
  tasks,
  onDrop,
}: {
  column: ColumnDef;
  tasks: ApiTask[];
  onDrop: (taskId: number, targetStatus: TaskStatus) => void;
}) {
  const [{ isOver }, drop] = useDrop(() => ({
    accept: 'TASK',
    drop: (item: { id: number }) => onDrop(item.id, column.status),
    collect: (monitor) => ({
      isOver: monitor.isOver(),
    }),
  }));

  return (
    <div ref={drop} className={`flex-1 min-w-[280px] ${isOver ? 'ring-2 ring-primary/40 rounded-lg' : ''}`}>
      <Card className="h-full">
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <h3>{column.title}</h3>
            <Badge variant="default">{tasks.length}</Badge>
          </div>
        </div>
        <div className="space-y-3">
          {tasks.map((task) => (
            <TaskCard key={task.id} task={task} />
          ))}
        </div>
      </Card>
    </div>
  );
}

export function Tasks() {
  const [tasks, setTasks] = useState<ApiTask[]>([]);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [newTitle, setNewTitle] = useState('');
  const [newDescription, setNewDescription] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiGet<Paginated<ApiTask>>('/api/tasks?per_page=200');
      setTasks(res.data);
      setMessage('');
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const tasksForStatus = (s: TaskStatus) =>
    tasks.filter((t) => t.status === s).sort((a, b) => a.position - b.position || b.id - a.id);

  const handleDrop = async (taskId: number, targetStatus: TaskStatus) => {
    const targetList = tasks.filter((t) => t.status === targetStatus);
    const position = targetList.length;
    try {
      await apiPatch(`/api/tasks/${taskId}`, { status: targetStatus, position });
      await load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '移動に失敗しました。');
    }
  };

  const createTask = async () => {
    if (!newTitle.trim()) return;
    setMessage('');
    try {
      await apiPost('/api/tasks', {
        title: newTitle.trim(),
        description: newDescription.trim() || null,
        status: 'todo',
      });
      setNewTitle('');
      setNewDescription('');
      await load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '作成に失敗しました。');
    }
  };

  return (
    <DndProvider backend={HTML5Backend}>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <h2 className="text-2xl">タスク管理</h2>
        </div>

        {message && <p className="text-sm text-destructive">{message}</p>}
        {loading && <p className="text-sm text-muted-foreground">読み込み中…</p>}

        <Card className="p-4 space-y-3">
          <h3 className="text-sm font-medium">新規タスク</h3>
          <input
            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="タイトル"
            value={newTitle}
            onChange={(e) => setNewTitle(e.target.value)}
          />
          <textarea
            className="w-full min-h-[60px] rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="説明（任意）"
            value={newDescription}
            onChange={(e) => setNewDescription(e.target.value)}
          />
          <Button type="button" className="gap-2" onClick={() => void createTask()}>
            <Plus className="w-4 h-4" />
            作成
          </Button>
        </Card>

        <div className="flex gap-4 overflow-x-auto pb-4">
          {COLUMNS.map((column) => (
            <TaskColumn
              key={column.id}
              column={column}
              tasks={tasksForStatus(column.status)}
              onDrop={(id, st) => void handleDrop(id, st)}
            />
          ))}
        </div>
      </div>
    </DndProvider>
  );
}
