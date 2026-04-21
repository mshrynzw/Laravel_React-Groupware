# 詳細設計書：ワークフロー（有給申請）

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/05_Phase2_ワークフロー_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/requests` | 一覧（自分の申請 or 承認待ち） |
| POST | `/api/requests` | 作成 |
| GET | `/api/requests/{id}` | 詳細 |
| POST | `/api/requests/{id}/submit` | 提出（下書き→申請中） |
| POST | `/api/requests/{id}/approve` | 承認 |
| POST | `/api/requests/{id}/reject` | 却下 |

---

## 3. データベース

### 3.1 `requests`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| user_id | FK | NO | 申請者 |
| type | string | NO | `paid_leave` 等 |
| status | string | NO | `draft`, `pending`, `approved`, `rejected`, `cancelled` |
| payload | jsonb | NO | 期間・理由等 |
| current_step | int | NO | 承認段階 |
| created_at / updated_at | timestamp | NO | |

### 3.2 `approvals`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| request_id | FK | NO | |
| step_order | int | NO | 1 始まり |
| approver_user_id | FK | YES | nullable、事前割当 or 動的 |
| result | string | YES | nullable、`approved`, `rejected`、null=未処理 |
| comment | text | YES | nullable |
| acted_at | datetime | YES | nullable |
| created_at / updated_at | timestamp | NO | |

---

## 4. payload スキーマ例（有給）

```json
{
  "schema_version": 1,
  "start_date": "2026-05-01",
  "end_date": "2026-05-03",
  "reason": "私用のため"
}
```

---

## 5. 承認フロー制御（案）

1. 申請作成時に `approvals` 行を `step_order` 順に生成（承認者は組織マスタから解決）。
2. `submit` で `status=pending`、最初の承認待ちへ。
3. `approve` は `current_step` の行の `approver_user_id` がログインユーザーと一致（または代理権限）を確認。
4. 最終段階まで承認されたら `requests.status=approved`。途中却下で `rejected`。

---

## 6. API 詳細

### POST `/api/requests`

**Request**

```json
{
  "type": "paid_leave",
  "payload": { }
}
```

`draft` で保存するか即 `submit` するかはクエリ `submit=1` で分岐してもよい。

### POST `/api/requests/{id}/reject`

```json
{
  "comment": "繁忙期のため"
}
```

---

## 7. Laravel クラス（案）

`RequestController`, `RequestPolicy`, `StoreRequestRequest`, `RequestWorkflowService`。

---

## 8. フロント

- 申請一覧タブ: 自分の申請 / 承認待ち
- 詳細でタイムライン表示（承認履歴）

---

## 9. テスト観点

- 承認順序の飛ばしができないこと
- 却下後に承認不可
