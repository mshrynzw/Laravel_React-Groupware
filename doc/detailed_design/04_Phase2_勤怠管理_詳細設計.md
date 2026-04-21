# 詳細設計書：勤怠管理

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/04_Phase2_勤怠管理_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 認可 |
|----------|-----|------|
| POST | `/api/attendance/clock-in` | 本人 |
| POST | `/api/attendance/clock-out` | 本人 |
| GET | `/api/attendance/records` | 本人 or 管理者（`user_id` 指定） |
| GET | `/api/attendance/summary` | 同上 |

---

## 3. データベース

### `attendance_records`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| user_id | FK | NO | |
| type | string または enum | NO | `clock_in`, `clock_out` |
| recorded_at | datetime | NO | サーバ時刻またはクライアント送信は要検討 |
| source | string | YES | nullable、`web`, `api` |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `(user_id, recorded_at)`。

---

## 4. 打刻ロジック（案）

1. **出勤**: 当日未出勤なら `clock_in` を追加。既に出勤済みなら 422 `already_clocked_in`。
2. **退勤**: 直近のペアが `clock_in` であること。未出勤なら 422。
3. タイムゾーン: `Asia/Tokyo` に正規化して「当日」を判定。

---

## 5. API 詳細

### POST `/api/attendance/clock-in`

**Response 201**

```json
{
  "data": {
    "id": 100,
    "type": "clock_in",
    "recorded_at": "2026-04-19T09:00:00+09:00"
  }
}
```

### GET `/api/attendance/records`

**Query**: `from`, `to`（ISO8601 日付）, `user_id`（管理者のみ）。

### GET `/api/attendance/summary`

**Query**: `month=2026-04` または `from`/`to`。

**Response 例**

```json
{
  "data": {
    "period": { "from": "2026-04-01", "to": "2026-04-30" },
    "days": [
      { "date": "2026-04-01", "work_minutes": 480, "status": "complete" }
    ],
    "total_work_minutes": 9600
  }
}
```

集計式は `AttendanceSummaryService` に集約。

---

## 6. フロント

| 画面 | 操作 |
|------|------|
| 打刻 | 大きなボタン、直近の出勤/退勤表示 |
| 一覧 | カレンダーまたは表、月切替 |

---

## 7. Laravel クラス（案）

`AttendanceController`, `ClockInRequest`, `AttendanceService`, `AttendanceSummaryService`。

---

## 8. テスト観点

- 二重出勤・未出勤の退勤が 422
- 月次集計の境界（月末）
