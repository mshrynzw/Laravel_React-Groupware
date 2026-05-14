# 詳細設計書：チャット

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/08_Phase4_チャット_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/chat/rooms` | 参加ルーム一覧 |
| POST | `/api/chat/rooms` | ルーム作成（任意） |
| GET | `/api/chat/rooms/{id}/messages` | メッセージ履歴（cursor ページング） |
| POST | `/api/chat/rooms/{id}/messages` | メッセージ送信 |

---

## 3. データベース

### `chat_rooms`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| name | string | YES | nullable、DM は相手名から生成でも可 |
| type | string | NO | `direct`, `group` |
| created_at / updated_at | timestamp | NO | |

### `chat_room_user`（参加）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| chat_room_id | FK | NO | |
| user_id | FK | NO | |
| created_at / updated_at | timestamp | NO | |

### `chat_messages`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| chat_room_id | FK | NO | |
| user_id | FK | NO | 送信者 |
| body | text | NO | |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `(chat_room_id, id)` で履歴取得。

---

## 4. リアルタイム（Laravel Reverb 想定）

| 項目 | 内容 |
|------|------|
| イベント | `App\Events\ChatMessageSent` → `broadcast(new ChatMessageSent($message))`（`ShouldBroadcastNow`） |
| チャネル | `PrivateChannel('chat.{roomId}')`（クライアント側は `private-chat.{roomId}`） |
| 認可 | `routes/channels.php` の `chat.{roomId}` でルーム参加者のみ購読可。`/broadcasting/auth` は `web` + `auth:sanctum` |

**フロント**: Laravel Echo + `pusher-js`（Reverb 互換エンドポイント）。

---

## 5. API 詳細

### POST `/api/chat/rooms/{id}/messages`

**Request**

```json
{
  "body": "こんにちは"
}
```

バリデーション: `body` required, max:5000。

**処理**

1. DB へ insert  
2. ブロードキャスト  
3. **Response 201** でメッセージ JSON を返す（Echo 二重表示防止のため `client_message_id` を付けてもよい）

### GET `/api/chat/rooms/{id}/messages`

**Query**: `before_id`（より古い履歴）, `limit`（デフォルト 50）。

---

## 6. Laravel クラス（案）

`ChatRoomController`, `ChatMessageController`, `App\Events\ChatMessageSent`, `routes/channels.php`（ルーム参加はコントローラ内のメンバー確認で実装）。

---

## 7. フロント

| 項目 | 内容 |
|------|------|
| 接続 | ログイン後に Echo 初期化（トークンまたは Sanctum Cookie） |
| UI | 左: ルーム一覧、右: メッセージ＋入力 |

---

## 8. テスト観点

- 非参加者がメッセージ POST で 403
- ブロードキャストペイロードに本文が含まれる（本番は長文制限）

---

## 9. MVP 実装メモ（リポジトリ現状）

- **リアルタイム**: `VITE_REVERB_*` を設定しバックエンドで `BROADCAST_CONNECTION=reverb` かつ `php artisan reverb:start` を動かすと、ログイン後に **Laravel Echo**（`pusher-js`）で `private-chat.{roomId}` を購読し、イベント名 **`.MessageSent`**（`broadcastAs`: `MessageSent`）でメッセージを追記する。未設定時は **HTTP ポーリング**（約 4 秒）のみ。併用時は Echo 受信に加え **約 30 秒**のバックアップポーリング。
- **API**: §2 の REST を実装済み。送信成功時に `ChatMessageSent` をブロードキャスト（`ChatMessageController`）。テストは `tests/Feature/Phase4ChatTest.php`（403、イベント dispatch 等）。
- **運用**: ローカル手順はリポジトリ `README.md`（バックエンドセットアップ内「チャットのリアルタイム」）および `src/backend/.env.example` / `src/frontend/.env.example` を参照。

