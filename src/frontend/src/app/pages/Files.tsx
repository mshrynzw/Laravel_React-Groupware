import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Button } from '../components/Button';
import { Badge } from '../components/Badge';
import { FileText, Image, File, FolderOpen, Upload, Download, MoreVertical } from 'lucide-react';

const files = [
  { id: 1, name: 'プロジェクト提案書.pdf', type: 'pdf', size: '2.4 MB', uploadedAt: '2026年4月15日', uploadedBy: '田中太郎' },
  { id: 2, name: 'デザインモックアップ.fig', type: 'figma', size: '8.1 MB', uploadedAt: '2026年4月14日', uploadedBy: '佐藤花子' },
  { id: 3, name: '会議議事録.docx', type: 'word', size: '156 KB', uploadedAt: '2026年4月13日', uploadedBy: '鈴木一郎' },
  { id: 4, name: 'ロゴデザイン.png', type: 'image', size: '3.2 MB', uploadedAt: '2026年4月12日', uploadedBy: '山田美咲' },
  { id: 5, name: 'データ分析.xlsx', type: 'excel', size: '1.8 MB', uploadedAt: '2026年4月11日', uploadedBy: '高橋健太' },
  { id: 6, name: 'プレゼン資料.pptx', type: 'powerpoint', size: '5.6 MB', uploadedAt: '2026年4月10日', uploadedBy: '渡辺直美' },
];

const folders = [
  { id: 1, name: 'プロジェクトA', count: 24 },
  { id: 2, name: 'マーケティング', count: 18 },
  { id: 3, name: 'デザイン', count: 32 },
  { id: 4, name: '営業資料', count: 15 },
];

function getFileIcon(type: string) {
  switch (type) {
    case 'pdf':
    case 'word':
      return <FileText className="w-8 h-8 text-primary" />;
    case 'image':
      return <Image className="w-8 h-8 text-secondary" />;
    default:
      return <File className="w-8 h-8 text-muted-foreground" />;
  }
}

export function Files() {
  return (
    <div className="space-y-6">
      {/* ヘッダー */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl">ファイル共有</h2>
        <Button className="gap-2">
          <Upload className="w-4 h-4" />
          ファイルをアップロード
        </Button>
      </div>

      {/* フォルダー */}
      <div>
        <h3 className="mb-4">フォルダー</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {folders.map((folder) => (
            <Card key={folder.id} className="hover:shadow-md transition-shadow cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                  <FolderOpen className="w-6 h-6 text-primary" />
                </div>
                <div className="flex-1">
                  <h4 className="mb-1">{folder.name}</h4>
                  <p className="text-xs text-muted-foreground">{folder.count}個のファイル</p>
                </div>
              </div>
            </Card>
          ))}
        </div>
      </div>

      {/* 最近のファイル */}
      <div>
        <h3 className="mb-4">最近のファイル</h3>
        <Card>
          <div className="divide-y divide-border">
            {files.map((file) => (
              <div key={file.id} className="flex items-center gap-4 p-4 hover:bg-accent transition-colors">
                <div className="w-12 h-12 rounded-lg bg-muted flex items-center justify-center flex-shrink-0">
                  {getFileIcon(file.type)}
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="mb-1 truncate">{file.name}</h4>
                  <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <span>{file.size}</span>
                    <span>•</span>
                    <span>{file.uploadedAt}</span>
                    <span>•</span>
                    <span>{file.uploadedBy}</span>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <button className="p-2 hover:bg-muted rounded-lg transition-colors">
                    <Download className="w-4 h-4" />
                  </button>
                  <button className="p-2 hover:bg-muted rounded-lg transition-colors">
                    <MoreVertical className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}
