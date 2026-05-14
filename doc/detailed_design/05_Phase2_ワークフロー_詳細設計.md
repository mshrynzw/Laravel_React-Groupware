# 詳細設計書：ワークフロー（有給申請）

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/05_Phase2_ワークフロー_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（実装）

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/requests` | 一覧（自分の申請 or `?mode=pending_approval` で承認待ち） |
| POST | `/api/requests` | 下書き作成 |
| GET | `/api/requests/{id}` | 詳細 |
| POST | `/api/requests/{id}/submit` | 提出（`draft` → `pending`）。ルール解決・`approvals` 生成・`request_rule_resolutions` 保存は `WorkflowRequestTransitionService::submitDraft` |
| POST | `/api/requests/{id}/approve` | 承認。現在段階の承認者のみ。本文に任意 `comment` |
| POST | `/api/requests/{id}/reject` | 却下。同上 |
| GET | `/api/workflow/rules` | 承認ルール一覧（admin 以上） |
| POST | `/api/workflow/rules` | 承認ルール作成（admin 以上） |
| PATCH | `/api/workflow/rules/{id}` | 承認ルール更新（admin 以上） |
| POST | `/api/workflow/rules/{id}/publish` | ルール版の公開（admin 以上） |
| POST | `/api/workflow/rules/{id}/activate` | 有効化（admin 以上） |
| POST | `/api/workflow/rules/{id}/deactivate` | 無効化（admin 以上） |
| GET | `/api/requests/{id}/resolution` | 適用ルール情報確認 |

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

### 3.3 `approval_rules`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| name | string | NO | ルール名 |
| request_type | string | NO | `paid_leave` 等 |
| scope_type | string | YES | `global`, `company`, `group` |
| scope_id | bigint | YES | scope 対象 ID |
| priority | int | NO | 値が大きいほど優先 |
| is_active | boolean | NO | |
| created_by | FK | NO | users.id |
| updated_by | FK | NO | users.id |
| created_at / updated_at | timestamp | NO | |

### 3.4 `approval_rule_versions`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| approval_rule_id | FK | NO | |
| version_no | int | NO | 1 から採番 |
| conditions | jsonb | NO | 一致条件 |
| steps | jsonb | NO | 承認ステップ定義 |
| is_published | boolean | NO | |
| published_at | datetime | YES | nullable |
| created_by | FK | NO | users.id |
| created_at / updated_at | timestamp | NO | |

### 3.5 `request_rule_resolutions`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| request_id | FK | NO | |
| approval_rule_id | FK | YES | 一致なし時は null 可 |
| approval_rule_version_id | FK | YES | 同上 |
| resolved_steps | jsonb | NO | 実際に採用した step 配列 |
| matched_context | jsonb | YES | 評価時コンテキスト |
| created_at / updated_at | timestamp | NO | |

---

## 4. payload / ルール JSON スキーマ例

```json
{
  "schema_version": 1,
  "start_date": "2026-05-01",
  "end_date": "2026-05-03",
  "reason": "私用のため"
}
```

### 4.0 `schema_version` 運用（実装）

| 項目 | 内容 |
|------|------|
| 既定値 | リクエスト本文に `payload.schema_version` が無い場合、サーバが **`1`** を付与して保存する（`StoreWorkflowRequest`）。 |
| バリデーション | 整数 `1`〜`99`。クライアントから明示送信可。 |
| 互換ポリシー | **v1** は現行の有給フィールド（`start_date` / `end_date` / `reason` / `leave_days`）。将来ペイロードを拡張する場合は `schema_version` を増分し、サーバで分岐またはマイグレーション変換を行う。 |
| 定数 | `App\Http\Requests\Workflow\StoreWorkflowRequest::DEFAULT_PAYLOAD_SCHEMA_VERSION` |

---

### 4.1 conditions 例

```json
{
  "leave_days": { "gte": 1, "lte": 5 },
  "applicant_group_ids": [10, 12],
  "applicant_roles": ["member", "leader"]
}
```

### 4.2 steps 例

```json
[
  {
    "step_order": 1,
    "assignee_type": "role",
    "assignee_value": "manager",
    "min_approvals": 1,
    "all_must_approve": false,
    "allow_delegate": false
  },
  {
    "step_order": 2,
    "assignee_type": "role",
    "assignee_value": "admin",
    "min_approvals": 1,
    "all_must_approve": false,
    "allow_delegate": false
  }
]
```

---

## 5. 承認フロー制御 / ルール解決

1. 申請作成時に `approvals` 行を `step_order` 順に生成（承認者はグループマスタから解決）。
2. `submit` で `status=pending`、最初の承認待ちへ。
3. `approve` は `current_step` の行の `approver_user_id` がログインユーザーと一致（または代理権限）を確認。
4. 最終段階まで承認されたら `requests.status=approved`。途中却下で `rejected`。

### 5.1 ルール解決アルゴリズム

1. `request_type` と `scope` で `is_active=true` の候補ルールを取得する。
2. `priority` 降順で `conditions` を評価し、最初に一致したルール版を採用する。
3. 一致ルールがない場合は `request_type` のデフォルトルールを採用する。
4. 採用した `steps` をもとに `approvals` を生成する。
5. 解決結果を `request_rule_resolutions` に保存する。

### 5.2 承認操作ガード

- current step 以外の承認は拒否する（順序飛ばし禁止）。
- `rejected` / `cancelled` 状態の申請は `approve` 不可。
- ルール管理 API は `admin` 以上のみ許可する。

---

### 5.3 状態遷移（実装）

| 操作 | 前提 | 結果（要約） |
|------|------|----------------|
| `submit` | `status=draft`、申請者本人 | `pending`, `current_step=1`、`approvals` 行生成、`request_rule_resolutions` 保存 |
| `approve` | `status=pending`、現在 `current_step` の `approver_user_id` が本人 | 当該 `approvals.result=approved`。次段ありなら `current_step++`、なければ `status=approved` |
| `reject` | 同上 | 当該 `approvals.result=rejected`、`status=rejected` |
| `approve` / `reject` | `rejected` / `cancelled` または `pending` 以外 | 422 |
| `approve` / `reject` | `pending` だが現在段の承認者でない | 403 |

永続化の本体は `WorkflowRequestTransitionService`（`submitDraft` / `recordApproval` / `recordRejection`）。現在段の承認者判定は `WorkflowRequestApprovalService::findApprovableApproval`。

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

### POST `/api/requests/{id}/approve`

**Request（任意）**

```json
{
  "comment": "承認コメント（任意）"
}
```

**Response 200**: `message` と `request`（リレーション付きオブジェクト）。

### POST `/api/requests/{id}/reject`

```json
{
  "comment": "繁忙期のため"
}
```

`comment` は任意だが UI では入力推奨。

### GET `/api/requests/{id}/resolution`

**Response 200**

```json
{
  "data": {
    "request_id": 1001,
    "approval_rule_id": 12,
    "approval_rule_version_id": 34,
    "resolved_steps": [ ]
  }
}
```

### POST `/api/workflow/rules`

**Request**

```json
{
  "name": "有給申請_通常",
  "request_type": "paid_leave",
  "scope_type": "group",
  "scope_id": 10,
  "priority": 100,
  "conditions": { },
  "steps": [ ]
}
```

---

## 7. Laravel クラス（実装マッピング）

| クラス | 役割 |
|--------|------|
| `WorkflowRequestController` | HTTP・認可・監査ログ呼び出し |
| `WorkflowRequestTransitionService` | 提出・承認・却下の状態遷移と `approvals` 更新 |
| `WorkflowRequestApprovalService` | 現在段の承認可能 `Approval` の解決 |
| `ApprovalRuleResolverService` | ルール版の解決（`submitDraft` 内で利用） |
| `ApprovalRuleController` | ルール CRUD・公開・有効/無効 |

---

## 8. フロント

- 申請一覧タブ: 自分の申請 / 承認待ち
- 詳細でタイムライン表示（承認履歴）

---

## 9. テスト観点

- 承認順序の飛ばしができないこと（結合: 非現在承認者の `approve` が 403）
- 却下後に承認不可
- admin 未満はルール管理 API が 403 になること
- submit 時に解決結果が `request_rule_resolutions` に保存されること
- `WorkflowRequestTransitionService` の単体（提出で `pending`、一段承認で `approved`、多段で `current_step` 進行、却下で `rejected`）
