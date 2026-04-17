import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { FileText, FolderOpen, Plus, Search } from 'lucide-react';
import { Input } from '../components/Input';

const wikiPages = [
  {
    id: 1,
    title: '新入社員向けガイド',
    category: 'オンボーディング',
    updatedAt: '2026年4月15日',
    author: '人事部'
  },
  {
    id: 2,
    title: '開発環境セットアップ手順',
    category: '開発',
    updatedAt: '2026年4月14日',
    author: '技術部'
  },
  {
    id: 3,
    title: '勤怠管理システムの使い方',
    category: '業務システム',
    updatedAt: '2026年4月13日',
    author: '総務部'
  },
  {
    id: 4,
    title: 'デザインガイドライン',
    category: 'デザイン',
    updatedAt: '2026年4月12日',
    author: 'デザイン部'
  },
  {
    id: 5,
    title: '経費精算の申請方法',
    category: '業務システム',
    updatedAt: '2026年4月10日',
    author: '経理部'
  },
  {
    id: 6,
    title: 'セキュリティポリシー',
    category: 'セキュリティ',
    updatedAt: '2026年4月8日',
    author: '情報システム部'
  }
];

const categories = ['すべて', 'オンボーディング', '開発', '業務システム', 'デザイン', 'セキュリティ'];

export function Wiki() {
  const [selectedCategory, setSelectedCategory] = React.useState('すべて');

  const filteredPages = selectedCategory === 'すべて' 
    ? wikiPages 
    : wikiPages.filter(page => page.category === selectedCategory);

  return (
    <div className="space-y-6">
      {/* ヘッダー */}
      <div className="flex items-center justify-between">
        <div className="flex-1 max-w-md">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="ページを検索..."
              className="w-full pl-10 pr-4 py-2 bg-input-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
        </div>
        <Button className="gap-2">
          <Plus className="w-4 h-4" />
          新規ページ作成
        </Button>
      </div>

      {/* カテゴリフィルター */}
      <Card>
        <div className="flex gap-2 flex-wrap">
          {categories.map((category) => (
            <button
              key={category}
              onClick={() => setSelectedCategory(category)}
              className={`px-4 py-2 rounded-lg transition-colors ${
                selectedCategory === category
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted hover:bg-muted/80'
              }`}
            >
              {category}
            </button>
          ))}
        </div>
      </Card>

      {/* ページリスト */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredPages.map((page) => (
          <Card key={page.id} className="hover:shadow-md transition-shadow cursor-pointer">
            <CardHeader>
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <FileText className="w-5 h-5 text-primary" />
                </div>
                <div className="flex-1">
                  <CardTitle className="mb-1">{page.title}</CardTitle>
                  <p className="text-xs text-muted-foreground">{page.category}</p>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-xs text-muted-foreground space-y-1">
                <div>更新日: {page.updatedAt}</div>
                <div>編集者: {page.author}</div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
