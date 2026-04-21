# 詳細設計書：お知らせ

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/06_Phase3_お知らせ_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 認可 |
|----------|-----|------|
| GET | `/api/announcements` | ログイン済み |
| GET | `/api/announcements/{id}` | 同上 |
| POST | `/api/announcements` | 管理者 |
| PUT/PATCH | `/api/announcements/{id}` | 管理者 |
| DELETE | `/api/announcements/{id}` | 管理者 |

---

## 3. データベース

### `announcements`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| title | string(255) | NO | |
| body | text | NO | HTML の場合はサニタイズ方針を決める |
| author_user_id | FK | NO | |
| published_at | datetime | YES | null は下書き |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `published_at DESC` で一覧最適化。

---

## 4. API 詳細

### GET `/api/announcements`

**Query**: `page`, `per_page`, `q`（タイトル検索）。

一般ユーザーには `published_at <= now()` のみ返す。管理者用に `include_draft=1` を別エンドポイントに分けてもよい（`/api/admin/announcements`）。

### POST `/api/announcements`

```json
{
  "title": "年度末のお知らせ",
  "body": "<p>...</p>",
  "published_at": "2026-04-01T09:00:00+09:00"
}
```

---

## 5. Laravel クラス（案）

`AnnouncementController`, `AnnouncementPolicy`, `StoreAnnouncementRequest`。

---

## 6. フロント

| 画面 | 内容 |
|------|------|
| 一覧 | カードまたはリスト、新着順 |
| 詳細 | 本文表示 |
| 管理 | 作成・編集フォーム（リッチテキストはライブラリ選定） |

---

## 7. セキュリティ

- `body` が HTML のとき `HTMLPurifier` 等でホワイトリスト。
- XSS を防ぎつつ表示は `dangerouslySetInnerHTML` は避けるかサニタイズ後のみ。

---

## 8. テスト観点

- 未公開が一般ユーザーに見えない
- 非管理者の POST が 403
