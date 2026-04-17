import React, { useState } from 'react';
import { Card } from '../components/Card';
import { Button } from '../components/Button';
import { Hash, Users, Send } from 'lucide-react';

const channels = [
  { id: 1, name: '全体', type: 'channel', unread: 0 },
  { id: 2, name: '開発チーム', type: 'channel', unread: 3 },
  { id: 3, name: 'デザインチーム', type: 'channel', unread: 0 },
  { id: 4, name: '営業チーム', type: 'channel', unread: 1 },
];

const messages = [
  { id: 1, user: '田中太郎', content: 'おはようございます！今日もよろしくお願いします。', time: '09:00', avatar: '田' },
  { id: 2, user: '佐藤花子', content: '新しいプロジェクトの資料、共有フォルダにアップしました。', time: '09:15', avatar: '佐' },
  { id: 3, user: '鈴木一郎', content: 'ありがとうございます！確認します。', time: '09:20', avatar: '鈴' },
  { id: 4, user: '山田美咲', content: 'デザインのレビュー、15:00からでいかがでしょうか？', time: '09:45', avatar: '山' },
];

export function Chat() {
  const [selectedChannel, setSelectedChannel] = useState(channels[1]);
  const [message, setMessage] = useState('');

  return (
    <div className="flex gap-6 h-[calc(100vh-10rem)]">
      {/* チャンネルリスト */}
      <Card className="w-64 flex-shrink-0">
        <div className="mb-4">
          <h3 className="mb-2">チャンネル</h3>
        </div>
        <div className="space-y-1">
          {channels.map((channel) => (
            <button
              key={channel.id}
              onClick={() => setSelectedChannel(channel)}
              className={`w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors ${
                selectedChannel.id === channel.id
                  ? 'bg-primary text-primary-foreground'
                  : 'hover:bg-accent'
              }`}
            >
              <div className="flex items-center gap-2">
                <Hash className="w-4 h-4" />
                <span className="text-sm">{channel.name}</span>
              </div>
              {channel.unread > 0 && (
                <span className="bg-destructive text-destructive-foreground text-xs px-2 py-0.5 rounded-full">
                  {channel.unread}
                </span>
              )}
            </button>
          ))}
        </div>
      </Card>

      {/* メッセージエリア */}
      <Card className="flex-1 flex flex-col">
        {/* チャンネルヘッダー */}
        <div className="pb-4 border-b border-border mb-4">
          <div className="flex items-center gap-2">
            <Hash className="w-5 h-5 text-primary" />
            <h3>{selectedChannel.name}</h3>
            <span className="text-sm text-muted-foreground ml-auto">
              <Users className="w-4 h-4 inline mr-1" />
              12人
            </span>
          </div>
        </div>

        {/* メッセージリスト */}
        <div className="flex-1 overflow-y-auto space-y-4 mb-4">
          {messages.map((msg) => (
            <div key={msg.id} className="flex gap-3">
              <div className="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                {msg.avatar}
              </div>
              <div className="flex-1">
                <div className="flex items-baseline gap-2 mb-1">
                  <span className="font-medium">{msg.user}</span>
                  <span className="text-xs text-muted-foreground">{msg.time}</span>
                </div>
                <p className="text-sm">{msg.content}</p>
              </div>
            </div>
          ))}
        </div>

        {/* 入力エリア */}
        <div className="flex gap-2">
          <input
            type="text"
            placeholder="メッセージを入力..."
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            className="flex-1 px-4 py-3 bg-input-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <Button className="px-6">
            <Send className="w-4 h-4" />
          </Button>
        </div>
      </Card>
    </div>
  );
}
