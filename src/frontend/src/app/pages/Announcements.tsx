import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '../components/Card';
import { Badge } from '../components/Badge';
import { Pin, Calendar } from 'lucide-react';

const announcements = [
  {
    id: 1,
    title: '【重要】全社会議のお知らせ',
    content: '来週月曜日10:00より、第3会議室にて全社会議を開催いたします。全員参加でお願いいたします。',
    date: '2026年4月15日',
    author: '総務部',
    pinned: true,
    category: '重要'
  },
  {
    id: 2,
    title: 'システムメンテナンスのお知らせ',
    content: '4月20日(日) 2:00-6:00の間、システムメンテナンスを実施いたします。この間、一部サービスがご利用いただけません。',
    date: '2026年4月14日',
    author: '情報システム部',
    pinned: false,
    category: 'システム'
  },
  {
    id: 3,
    title: '新入社員歓迎会のご案内',
    content: '4月25日(金)18:00より、新入社員歓迎会を開催いたします。ぜひご参加ください。',
    date: '2026年4月13日',
    author: '人事部',
    pinned: false,
    category: 'イベント'
  },
  {
    id: 4,
    title: '夏季休暇申請の受付開始',
    content: '夏季休暇の申請受付を開始いたしました。ワークフローシステムよりお申し込みください。',
    date: '2026年4月12日',
    author: '人事部',
    pinned: false,
    category: '休暇'
  },
  {
    id: 5,
    title: 'セキュリティ研修のお知らせ',
    content: '全社員対象のセキュリティ研修を5月に実施いたします。詳細は後日お知らせいたします。',
    date: '2026年4月10日',
    author: '情報システム部',
    pinned: false,
    category: '研修'
  }
];

export function Announcements() {
  return (
    <div className="space-y-6">
      {/* ピン留めされたお知らせ */}
      {announcements.filter(a => a.pinned).map((announcement) => (
        <Card key={announcement.id} className="border-primary/30 bg-primary/5">
          <CardHeader>
            <div className="flex items-start justify-between gap-4">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <Pin className="w-4 h-4 text-primary" />
                  <Badge variant="error">{announcement.category}</Badge>
                </div>
                <CardTitle>{announcement.title}</CardTitle>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-sm mb-4 leading-relaxed">{announcement.content}</p>
            <div className="flex items-center gap-4 text-xs text-muted-foreground">
              <div className="flex items-center gap-1">
                <Calendar className="w-3 h-3" />
                {announcement.date}
              </div>
              <div>投稿者: {announcement.author}</div>
            </div>
          </CardContent>
        </Card>
      ))}

      {/* 通常のお知らせ */}
      <div className="grid gap-4">
        {announcements.filter(a => !a.pinned).map((announcement) => (
          <Card key={announcement.id} className="hover:shadow-md transition-shadow cursor-pointer">
            <CardHeader>
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="mb-2">
                    <Badge variant="default">{announcement.category}</Badge>
                  </div>
                  <CardTitle>{announcement.title}</CardTitle>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-sm mb-4 leading-relaxed text-muted-foreground">
                {announcement.content}
              </p>
              <div className="flex items-center gap-4 text-xs text-muted-foreground">
                <div className="flex items-center gap-1">
                  <Calendar className="w-3 h-3" />
                  {announcement.date}
                </div>
                <div>投稿者: {announcement.author}</div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
