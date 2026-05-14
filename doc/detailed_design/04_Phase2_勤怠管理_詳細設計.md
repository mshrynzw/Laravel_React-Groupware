# 詳細設計書：勤怠管理

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/04_Phase2_勤怠管理_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（実装）

| メソッド | URI | 認可 |
|----------|-----|------|
| POST | `/api/attendance/clock-in` | 本人（Sanctum 認証済み） |
| POST | `/api/attendance/clock-out` | 本人 |
| GET | `/api/attendance/records` | 本人。`user_id` 指定時は `admin` / `superadmin` のみ（admin は対象ユーザーと同一グループ所属が必要） |
| GET | `/api/attendance/summary` | 同上 |

---

## 3. データベース

### `attendance_records`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| user_id | FK | NO | |
| type | string | NO | `clock_in`, `clock_out` |
| recorded_at | timestamptz | NO | サーバ時刻（UTC 保管、API は ISO8601） |
| source | string | YES | nullable。例: `web` |
| created_at / updated_at | timestamp | NO | |

**インデックス**: `(user_id, recorded_at)`。

---

## 4. 打刻ロジック（実装）

実装は `AttendanceClockService`。

1. **出勤**: ユーザーの**直近**打刻が `clock_in` のまま（未退勤）なら 422（二重出勤）。それ以外は `clock_in` を追加。
2. **退勤**: 直近が `clock_in` でない場合は 422（未出勤退勤）。
3. **集計の日付境界**: `AttendanceSummaryService` が `Asia/Tokyo` の暦日で `from`〜`to` を解釈し、`recorded_at` を UTC 範囲に変換して照会する。

---

## 5. API 詳細（実装）

### 5.1 POST `/api/attendance/clock-in` / POST `/api/attendance/clock-out`

**Request（任意）**

```json
{ "source": "web" }
```

**Response 201**

```json
{
  "message": "出勤を記録しました。",
  "data": {
    "id": 100,
    "type": "clock_in",
    "recorded_at": "2026-04-19T00:00:00+00:00",
    "source": "web"
  }
}
```

**Response 422**（業務エラー例・打刻）

Laravel `ValidationException` 形式。`errors.code` に配列でメッセージが入る。

| 状況 | `errors.code` 先頭メッセージの意味 |
|------|-------------------------------------|
| 二重出勤 | 直近が未退勤の `clock_in` |
| 未出勤退勤 | 直近が `clock_out` または打刻なし |

**監査**: `attendance.clock_in` / `attendance.clock_out`（`AuditLogger`）。

---

### 5.2 GET `/api/attendance/records`

**Query（必須）**: `from`=`YYYY-MM-DD`, `to`=`YYYY-MM-DD`（`to` ≥ `from`）。任意: `user_id`, `per_page`（既定 50）。

**Response 200**: Laravel ページネーション JSON（`data` に `AttendanceRecord` 配列、`meta` にページ情報）。横断仕様の「一覧 + meta」と整合。

---

### 5.3 GET `/api/attendance/summary`

**Query（いずれか）**

- `month=YYYY-MM` … その月 1 日〜末日（東京暦）
- または `from` / `to`（`YYYY-MM-DD`）

任意: `user_id`（管理者参照時）。

**Response 200**

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

`days[].status`: `none`（打刻なし） / `complete`（当日ペア成立） / `incomplete`（当日未退勤の `clock_in` が期間末まで残存）。

集計ロジックは `AttendanceSummaryService`。

---

## 6. フロント

| 画面 | 操作 |
|------|------|
| `/attendance` | 出勤・退勤、東京日付の「本日」状態、月指定の打刻一覧と `summary` の総勤務時間 |
| `/dashboard` | 本日打刻状態・未承認件数の API 取得、勤怠・ワークフローへの導線 |

日付ヘルパは `src/app/lib/attendanceDates.ts` に集約。

---

## 7. Laravel クラス（実装）

| クラス | 役割 |
|--------|------|
| `AttendanceController` | ルート・認可・レスポンス |
| `AttendanceClockService` | 打刻可否と永続化 |
| `AttendanceSummaryService` | 期間集計 |

---

## 8. テスト観点

- 二重出勤・未出勤の退勤が 422
- 月次集計の境界（月末）
- `AttendanceSummaryService` の単体（ペア集計・未退勤 `incomplete`）
