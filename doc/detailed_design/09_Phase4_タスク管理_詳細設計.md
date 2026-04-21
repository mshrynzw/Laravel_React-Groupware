# 詳細設計書：タスク管理

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/09_Phase4_タスク管理_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI |
|----------|-----|
| GET | `/api/tasks` |
| POST | `/api/tasks` |
| GET | `/api/tasks/{id}` |
| PUT/PATCH | `/api/tasks/{id}` |
| DELETE | `/api/tasks/{id}` |

---

## 3. データベース

### `tasks`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| title | string | NO | |
| description | text | YES | nullable |
| status | string | NO | `todo`, `in_progress`, `done` 等 |
| due_at | datetime | YES | nullable |
| creator_user_id | FK | NO | |
| assignee_user_id | FK | YES | nullable |
| position | int default 0 | NO | カンバン列内順 |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `(status, position)`、`(assignee_user_id)`。

---

## 4. API 詳細

### GET `/api/tasks`

**Query**: `status`, `assignee_user_id`, `page`。

### PATCH `/api/tasks/{id}`

ドラッグでステータス＋順序更新:

```json
{
  "status": "in_progress",
  "position": 2
}
```

楽観ロック: `updated_at` を条件に `where` し、0 件なら 409 Conflict（任意）。

---

## 5. Laravel クラス（案）

`TaskController`, `TaskPolicy`, `UpdateTaskRequest`。

---

## 6. フロント

| ライブラリ候補 | 用途 |
|----------------|------|
| `@dnd-kit` 等 | カンバン DnD |

列は `status` ごとにグループ化して表示。

---

## 7. テスト観点

- 担当者以外の編集が 403（ポリシー次第）
- ステータス遷移の不正値が 422
