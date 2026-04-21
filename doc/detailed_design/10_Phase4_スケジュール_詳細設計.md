# 詳細設計書：スケジュール

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/10_Phase4_スケジュール_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI |
|----------|-----|
| GET | `/api/schedules` |
| POST | `/api/schedules` |
| GET | `/api/schedules/{id}` |
| PUT/PATCH | `/api/schedules/{id}` |
| DELETE | `/api/schedules/{id}` |

---

## 3. データベース

### `schedules`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| user_id | FK | NO | 所有者 |
| title | string | NO | |
| description | text | YES | nullable |
| start_at | datetime | NO | UTC 保存推奨 |
| end_at | datetime | NO | `start_at` 以上 |
| all_day | boolean default false | NO | true の場合は日付のみ扱いのルールを定義 |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `(user_id, start_at)` で期間検索。

期間重複クエリ例（PostgreSQL）:

```sql
WHERE user_id = ? AND start_at < ? AND end_at > ?
```

---

## 4. API 詳細

### GET `/api/schedules`

**Query**

| パラメータ | 必須 | 説明 |
|------------|------|------|
| from | ○ | ISO8601 |
| to | ○ | ISO8601 |
| user_id | △ | 省略時はログインユーザー。他人は共有設定時のみ |

**Response**

```json
{
  "data": [
    {
      "id": 1,
      "title": "定例",
      "start_at": "2026-04-19T10:00:00+09:00",
      "end_at": "2026-04-19T11:00:00+09:00",
      "all_day": false
    }
  ]
}
```

### POST `/api/schedules`

```json
{
  "title": "定例",
  "start_at": "2026-04-19T10:00:00+09:00",
  "end_at": "2026-04-19T11:00:00+09:00",
  "all_day": false
}
```

---

## 5. フロント

| 項目 | 内容 |
|------|------|
| カレンダー | FullCalendar 等、`from`/`to` をビュー切替で再取得 |
| フォーム | タイムゾーンはブラウザ→API は ISO 文字列 |

---

## 6. Laravel クラス（案）

`ScheduleController`, `SchedulePolicy`, `StoreScheduleRequest`。

---

## 7. テスト観点

- `end_at < start_at` が 422
- 期間外のイベントが一覧に含まれないクエリの境界
