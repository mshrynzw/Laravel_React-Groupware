import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { Search as SearchIcon, FileText, MessageSquare, Users, Calendar } from 'lucide-react';

const searchResults = [
  {
    id: 1,
    type: 'document',
    title: 'プロジェクト提案書 2026年度版',
    description: '新規プロジェクトの提案資料です。市場分析と予算計画を含みます。',
    date: '2026年4月15日',
    author: '田中太郎',
  },
  {
    id: 2,
    type: 'chat',
    title: 'デザインチーム - 新UIについて',
    description: '新しいUIデザインのレビュー会議の議論内容です。',
    date: '2026年4月14日',
    author: '佐藤花子',
  },
  {
    id: 3,
    type: 'person',
    title: '鈴木一郎',
    description: '開発部 - シニアエンジニア',
    date: '所属: 開発部',
    author: '',
  },
  {
    id: 4,
    type: 'event',
    title: '全体会議',
    description: '月次の全体会議。各部署からの進捗報告を行います。',
    date: '2026年4月20日 10:00',
    author: '総務部',
  },
  {
    id: 5,
    type: 'document',
    title: '勤怠管理マニュアル',
    description: '勤怠管理システムの使用方法とよくある質問をまとめた資料です。',
    date: '2026年4月10日',
    author: '人事部',
  },
];

const typeConfig = {
  document: { icon: FileText, label: 'ドキュメント', color: 'text-primary' },
  chat: { icon: MessageSquare, label: 'チャット', color: 'text-secondary' },
  person: { icon: Users, label: '社員', color: 'text-purple-500' },
  event: { icon: Calendar, label: 'イベント', color: 'text-orange-500' },
};

export function Search() {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedType, setSelectedType] = useState<string | null>(null);

  const filteredResults = selectedType 
    ? searchResults.filter(result => result.type === selectedType)
    : searchResults;

  return (
    <div className="space-y-6">
      {/* 検索バー */}
      <Card className="bg-gradient-to-br from-primary/5 to-secondary/5">
        <CardContent className="py-8">
          <div className="max-w-2xl mx-auto">
            <h2 className="text-2xl text-center mb-6">社内検索</h2>
            <div className="relative">
              <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
              <input
                type="text"
                placeholder="ドキュメント、チャット、社員、イベントを検索..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-12 pr-4 py-4 bg-card border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-ring text-lg"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* フィルター */}
      <div className="flex gap-2 flex-wrap">
        <button
          onClick={() => setSelectedType(null)}
          className={`px-4 py-2 rounded-lg transition-colors ${
            selectedType === null
              ? 'bg-primary text-primary-foreground'
              : 'bg-muted hover:bg-muted/80'
          }`}
        >
          すべて
        </button>
        {Object.entries(typeConfig).map(([type, config]) => (
          <button
            key={type}
            onClick={() => setSelectedType(type)}
            className={`px-4 py-2 rounded-lg transition-colors flex items-center gap-2 ${
              selectedType === type
                ? 'bg-primary text-primary-foreground'
                : 'bg-muted hover:bg-muted/80'
            }`}
          >
            <config.icon className="w-4 h-4" />
            {config.label}
          </button>
        ))}
      </div>

      {/* 検索結果 */}
      <div>
        <div className="mb-4 text-sm text-muted-foreground">
          {filteredResults.length}件の結果
        </div>
        <div className="space-y-4">
          {filteredResults.map((result) => {
            const TypeIcon = typeConfig[result.type as keyof typeof typeConfig].icon;
            const typeColor = typeConfig[result.type as keyof typeof typeConfig].color;
            const typeLabel = typeConfig[result.type as keyof typeof typeConfig].label;

            return (
              <Card key={result.id} className="hover:shadow-md transition-shadow cursor-pointer">
                <CardContent className="p-6">
                  <div className="flex items-start gap-4">
                    <div className={`w-12 h-12 rounded-lg bg-muted flex items-center justify-center flex-shrink-0 ${typeColor}`}>
                      <TypeIcon className="w-6 h-6" />
                    </div>
                    <div className="flex-1">
                      <div className="flex items-start justify-between gap-4 mb-2">
                        <h4>{result.title}</h4>
                        <Badge variant="default">{typeLabel}</Badge>
                      </div>
                      <p className="text-sm text-muted-foreground mb-3">
                        {result.description}
                      </p>
                      <div className="flex items-center gap-3 text-xs text-muted-foreground">
                        <span>{result.date}</span>
                        {result.author && (
                          <>
                            <span>•</span>
                            <span>{result.author}</span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      </div>
    </div>
  );
}
