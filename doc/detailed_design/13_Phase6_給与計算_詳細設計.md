# 詳細設計書：給与計算

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/13_Phase6_給与計算_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. スコープと注意

給与計算は法令・社内規程に依存する。**本書はシステム上のデータ構造と処理フローのみ** を示し、計算式の正確性は人事・労務の確認を前提とする。

---

## 3. ルート（案）

| メソッド | URI | 認可 |
|----------|-----|------|
| POST | `/api/payroll/runs` | 給与管理者 |
| GET | `/api/payroll/runs/{id}` | 管理者 or 監査 |
| GET | `/api/payroll/slips` | 本人は自分のみ、管理者は全件 |
| GET | `/api/payroll/slips/{id}` | 同上 |

---

## 4. データベース

### `payroll_runs`（計算バッチ）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| period_year | int | NO | |
| period_month | int | NO | 1–12 |
| status | string | NO | `pending`, `completed`, `failed` |
| executed_by | FK | NO | |
| executed_at | datetime | YES | nullable |
| created_at / updated_at | timestamp | NO | |

### `payroll_slips`（明細）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| payroll_run_id | FK | NO | |
| user_id | FK | NO | |
| gross_amount | decimal(12,2) | NO | |
| net_amount | decimal(12,2) | NO | |
| breakdown | jsonb | NO | 内訳スナップショット |
| created_at / updated_at | timestamp | NO | |

**制約**: `(payroll_run_id, user_id)` unique。

---

## 5. breakdown JSON 例

```json
{
  "schema_version": 1,
  "base_salary": 300000,
  "allowances": [{ "name": "住宅", "amount": 15000 }],
  "deductions": [{ "name": "健康保険", "amount": 15000 }],
  "attendance": {
    "work_days": 20,
    "absence_days": 0
  }
}
```

---

## 6. 処理フロー（POST `/api/payroll/runs`）

1. 対象月・対象ユーザー一覧を確定（在籍者）。
2. 勤怠サマリを `AttendanceSummaryService` から取得。
3. `PayrollCalculationService` が金額を算出し `payroll_slips` に INSERT。
4. `status=completed`。失敗時はトランザクションロールバックし `failed`。

再実行は **同一 period の completed がある場合は 409**（明示削除後のみ再計算など方針を決める）。

---

## 7. API 詳細

### POST `/api/payroll/runs`

```json
{
  "year": 2026,
  "month": 4
}
```

**Response 202** 長時間ならキュー投入し `run_id` を返す運用も可。

### GET `/api/payroll/slips/{id}`

本人は `slip.user_id === auth.id` のみ。管理者は全件。

---

## 8. セキュリティ

- 給与 API は厳格な Policy + 監査ログ
- `breakdown` に個人住所等を含めない方針を検討

---

## 9. Laravel クラス（案）

`PayrollRunController`, `PayrollSlipController`, `PayrollPolicy`, `PayrollCalculationService`, `RunPayrollJob`（キュー）。

---

## 10. テスト観点

- 本人以外の明細 GET が 403
- 二重計算が防止されること（409）
