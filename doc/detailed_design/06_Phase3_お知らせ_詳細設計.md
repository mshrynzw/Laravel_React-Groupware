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
| GET | `/api/announcements` | ログイン済み（公開済みのみ） |
| GET | `/api/admin/announcements` | `admin` / `superadmin`（下書き・予約投稿を含む） |
| GET | `/api/announcements/{id}` | ログイン済み（公開済みのみ。下書き参照は管理者のみ別ルートでも可） |
| POST | `/api/announcements` | `admin` / `superadmin` |
| PATCH | `/api/announcements/{id}` | `admin` / `superadmin` |
| PUT | `/api/announcements/{id}` | `admin` / `superadmin`（`PATCH` と同一処理） |
| DELETE | `/api/announcements/{id}` | `admin` / `superadmin` |

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
| updated_by | FK | YES | 任意。最終更新者（nullable） |

**インデックス**: `published_at DESC` で一覧最適化。

---

## 4. API 詳細

### GET `/api/announcements`

**Query**: `page`, `per_page`, `q`（タイトル部分一致）。

**公開条件**: `published_at IS NOT NULL AND published_at <= now()` の行のみ。

### GET `/api/admin/announcements`

**Query**: 同上。下書き（`published_at` NULL）および予約投稿（未来の `published_at`）を **含む**。`admin` / `superadmin` のみ。

### GET `/api/announcements/{id}`

一般: **公開済み**の行のみ返す。下書き・予約は **404**（または 403。実装時に統一）。

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

**実装（MVP）**: サーバ側で `App\Support\HtmlSanitizer`（`strip_tags` による許可タグのみ＋`javascript:` / `<script` パターンのバリデーション拒否）を `StoreAnnouncementRequest` / `UpdateAnnouncementRequest` 経由で適用。フロントはサニタイズ済み HTML を表示する前提（詳細画面は `dangerouslySetInnerHTML` 使用）。将来 HTMLPurifier 等へ差し替える場合は本節とテストを更新する。

### 7.1 API メソッド（更新）

- お知らせの更新は **`PATCH`** に加え **`PUT /api/announcements/{id}`** も同一ハンドラで受け付ける（クライアント互換用）。

---

## 8. テスト観点

- 未公開が一般ユーザーに見えない
- 非管理者の POST が 403
- 予約投稿（未来 `published_at`）が一般一覧に出ない
- 監査ログに `request_id` が付く（横断: `X-Request-Id`）

---

## 9. 横断仕様との整合

| 項目 | 方針 |
|------|------|
| API ベース | `/api/announcements`（`doc/detailed_design/90_横断タスク_詳細設計.md`） |
| 認証 | Sanctum SPA Cookie（Phase2 と同様） |
| 一覧 | Laravel `paginate` → `data` + `meta` |
| 相関 ID | リクエスト／レスポンスの `X-Request-Id`（ミドルウェア） |
| 失敗 | `message` + `errors`（422 バリデーション） |

---

## 10. 監査イベント（案）

| event | タイミング |
|-------|------------|
| `announcement.created` | 作成 |
| `announcement.updated` | 更新 |
| `announcement.deleted` | 削除 |

`published_at` の変更は `updated` の `payload` に before/after を含める。

---

## 11. 管理者一覧と一般一覧

§2 のとおり **エンドポイント分離**を採用する（`/api/announcements` と `/api/admin/announcements`）。
