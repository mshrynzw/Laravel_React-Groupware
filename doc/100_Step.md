# 🎯 全体WBS構造

```txt
Phase1：基盤
Phase2：業務（勤怠・ワークフロー）
Phase3：情報共有
Phase4：コラボ
Phase5：高度機能
```

---

# 🟢 PHASE1：認証・基盤

## 認証（Sanctum）

```txt
- Sanctum導入
- ログインAPI
- ログアウトAPI
- セッション管理
- CSRF対応
- フロントログイン画面作成
- 認証ガード（Route Guard）
```

---

## ユーザー管理

```txt
- usersテーブル作成
- ユーザーCRUD API
- ロール管理（rolesテーブル）
- 権限制御（RBAC）
- ユーザー一覧画面
```

---

## グループ管理

```txt
- companiesテーブル
- groupsテーブル
- user_company関連付け
- グループツリー表示
```

---

# 🔥 PHASE2：業務（最重要）

## 勤怠管理

```txt
- attendance_recordsテーブル
- 打刻API（出勤/退勤）
- 勤務履歴取得API
- 日別・月別集計ロジック
- 打刻UI
- 勤怠一覧画面
```

---

## ワークフロー（有給申請）

```txt
- requestsテーブル（JSONB）
- approvalsテーブル
- 申請作成API
- 承認API
- 承認フロー制御ロジック
- 申請一覧画面
- 承認UI
```

---

# 🟡 PHASE3：情報共有

## お知らせ

```txt
- announcementsテーブル
- 投稿API
- 一覧取得API
- 詳細API
- 管理者投稿画面
- 一覧UI
```

---

## ファイル共有

```txt
- filesテーブル
- S3 or ローカル保存設計
- アップロードAPI
- ダウンロードAPI
- ファイル一覧UI
```

---

# 🔵 PHASE4：コラボ

## チャット

```txt
- chat_roomsテーブル
- chat_messagesテーブル
- メッセージ送信API
- WebSocket導入（Laravel Reverbなど）
- リアルタイム更新
- チャットUI
```

---

## タスク管理

```txt
- tasksテーブル
- タスクCRUD API
- ステータス管理
- 担当者紐付け
- カンバンUI
```

---

## スケジュール

```txt
- schedulesテーブル
- イベントCRUD API
- カレンダー表示ロジック
- 月/週表示UI
```

---

# 🟣 PHASE5：ナレッジ

## Wiki

```txt
- wiki_pagesテーブル
- 編集API
- バージョン管理（任意）
- エディタUI
```

---

# 🔴 PHASE6：高度機能

## 社内検索

```txt
- 全テーブル横断検索設計
- インデックス設計
- 検索API
- 検索UI
```

---

## 給与計算

```txt
- payrollテーブル
- 勤怠データ集計ロジック
- 給与計算ロジック
- 明細生成
- 明細UI
```

---

# 💡 横断タスク（全フェーズ共通）

```txt
- API設計
- エラーハンドリング
- ログ管理
- 権限制御
- バリデーション
- テスト（後回しでもOK）
```
