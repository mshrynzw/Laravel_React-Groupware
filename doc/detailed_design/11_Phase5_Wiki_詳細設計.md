# 詳細設計書：Wiki

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/11_Phase5_Wiki_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/wiki/pages` | 一覧・ツリー |
| GET | `/api/wiki/pages/by-slug/{slug}` | スラッグ取得 |
| POST | `/api/wiki/pages` | 作成 |
| PUT | `/api/wiki/pages/{id}` | 更新 |

スラッグ変更時はリダイレクト用に `redirect_from_slug` を別テーブルで管理してもよい。

---

## 3. データベース

### `wiki_pages`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| slug | string unique | NO | URL 用英数字ハイフン |
| title | string | NO | |
| body | longtext | NO | Markdown 推奨 |
| parent_id | bigint FK | YES | nullable、階層 |
| updated_by | FK | YES | nullable、最終更新者 |
| created_at / updated_at | timestamp | NO | |

### `wiki_revisions`（任意）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| wiki_page_id | FK | NO | |
| body | longtext | NO | |
| editor_user_id | FK | NO | |
| created_at / updated_at | timestamp | NO | |

更新時にコピーを保存。最新本文は `wiki_pages.body` にも保持（参照高速化）。

---

## 4. API 詳細

### PUT `/api/wiki/pages/{id}`

**Request**

```json
{
  "title": "手順書",
  "body": "# 概要\n...",
  "commit_message": "誤字修正"
}
```

**処理**

1. 楽観ロック: `updated_at` がリクエストと一致するか（ヘッダ `If-Match` または body `updated_at`）
2. `wiki_revisions` に挿入
3. `wiki_pages` を更新

競合時 409 + 相手の内容（任意）。

---

## 5. フロント

| 項目 | 内容 |
|------|------|
| 表示 | Markdown レンダラ（`react-markdown`）、コードハイライト |
| 編集 | 左右分割プレビューまたはタブ |

---

## 6. Laravel クラス（案）

`WikiPageController`, `WikiPagePolicy`, `UpdateWikiPageRequest`, `WikiRevisionService`。

---

## 7. テスト観点

- スラッグ重複で 422
- リビジョンが 1 更新につき 1 件増えること
