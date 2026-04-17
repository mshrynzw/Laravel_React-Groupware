import React, { useState } from 'react';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { Plus, MoreVertical } from 'lucide-react';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';

interface Task {
  id: number;
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  assignee: string;
}

interface Column {
  id: string;
  title: string;
  tasks: Task[];
}

const initialColumns: Column[] = [
  {
    id: 'todo',
    title: '未着手',
    tasks: [
      { id: 1, title: 'デザインレビュー', description: 'UIデザインの確認', priority: 'high', assignee: '田中' },
      { id: 2, title: 'API設計', description: 'エンドポイントの設計', priority: 'medium', assignee: '佐藤' },
    ]
  },
  {
    id: 'inProgress',
    title: '進行中',
    tasks: [
      { id: 3, title: 'データベース設計', description: 'テーブル構造の設計', priority: 'high', assignee: '鈴木' },
      { id: 4, title: 'テスト実装', description: 'ユニットテストの作成', priority: 'low', assignee: '山田' },
    ]
  },
  {
    id: 'review',
    title: 'レビュー',
    tasks: [
      { id: 5, title: 'コードレビュー', description: 'プルリクエストの確認', priority: 'medium', assignee: '高橋' },
    ]
  },
  {
    id: 'done',
    title: '完了',
    tasks: [
      { id: 6, title: '要件定義', description: 'プロジェクト要件の整理', priority: 'high', assignee: '渡辺' },
      { id: 7, title: 'キックオフミーティング', description: 'プロジェクト開始会議', priority: 'medium', assignee: '伊藤' },
    ]
  }
];

function TaskCard({ task }: { task: Task }) {
  const [{ isDragging }, drag] = useDrag(() => ({
    type: 'TASK',
    item: { id: task.id },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  }));

  const priorityColors = {
    high: 'error',
    medium: 'warning',
    low: 'info',
  } as const;

  const priorityLabels = {
    high: '高',
    medium: '中',
    low: '低',
  };

  return (
    <div
      ref={drag}
      className={`bg-card border border-border rounded-lg p-3 cursor-move hover:shadow-md transition-shadow ${
        isDragging ? 'opacity-50' : ''
      }`}
    >
      <div className="flex items-start justify-between mb-2">
        <h4 className="text-sm flex-1">{task.title}</h4>
        <button className="p-1 hover:bg-accent rounded">
          <MoreVertical className="w-3 h-3" />
        </button>
      </div>
      <p className="text-xs text-muted-foreground mb-3">{task.description}</p>
      <div className="flex items-center justify-between">
        <Badge variant={priorityColors[task.priority]} className="text-xs">
          {priorityLabels[task.priority]}
        </Badge>
        <div className="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs">
          {task.assignee[0]}
        </div>
      </div>
    </div>
  );
}

function TaskColumn({ column, onDrop }: { column: Column; onDrop: (taskId: number, columnId: string) => void }) {
  const [{ isOver }, drop] = useDrop(() => ({
    accept: 'TASK',
    drop: (item: { id: number }) => onDrop(item.id, column.id),
    collect: (monitor) => ({
      isOver: monitor.isOver(),
    }),
  }));

  return (
    <div
      ref={drop}
      className={`flex-1 min-w-[280px] ${isOver ? 'opacity-50' : ''}`}
    >
      <Card className="h-full">
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <h3>{column.title}</h3>
            <Badge variant="default">{column.tasks.length}</Badge>
          </div>
          <button className="p-1 hover:bg-accent rounded">
            <Plus className="w-4 h-4" />
          </button>
        </div>
        <div className="space-y-3">
          {column.tasks.map((task) => (
            <TaskCard key={task.id} task={task} />
          ))}
        </div>
      </Card>
    </div>
  );
}

export function Tasks() {
  const [columns, setColumns] = useState(initialColumns);

  const handleDrop = (taskId: number, targetColumnId: string) => {
    setColumns((prevColumns) => {
      // タスクを探して移動
      let taskToMove: Task | null = null;
      const newColumns = prevColumns.map((column) => ({
        ...column,
        tasks: column.tasks.filter((task) => {
          if (task.id === taskId) {
            taskToMove = task;
            return false;
          }
          return true;
        }),
      }));

      // 新しいカラムに追加
      if (taskToMove) {
        const targetColumn = newColumns.find((col) => col.id === targetColumnId);
        if (targetColumn) {
          targetColumn.tasks.push(taskToMove);
        }
      }

      return newColumns;
    });
  };

  return (
    <DndProvider backend={HTML5Backend}>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl">タスク管理</h2>
          <Button className="gap-2">
            <Plus className="w-4 h-4" />
            新規タスク
          </Button>
        </div>

        <div className="flex gap-4 overflow-x-auto pb-4">
          {columns.map((column) => (
            <TaskColumn key={column.id} column={column} onDrop={handleDrop} />
          ))}
        </div>
      </div>
    </DndProvider>
  );
}
