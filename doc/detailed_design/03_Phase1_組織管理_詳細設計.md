# 詳細設計書：グループ管理

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/03_Phase1_グループ管理_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

| メソッド | URI | 認可 |
|----------|-----|------|
| GET | `/api/companies` | ログイン済み |
| GET | `/api/companies/{id}` | 同上 |
| POST/PUT/DELETE | `/api/companies` … | 管理者 |
| GET | `/api/groups` | ログイン済み |
| POST/PUT/DELETE | `/api/groups` … | 管理者 |

---

## 3. データベース

### 3.1 `companies`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| name | string | NO | |
| code | string unique | YES | nullable、社員コード体系等 |
| parent_id | bigint FK → companies.id | YES | nullable、親子を同一テーブルで表す場合 |
| created_at / updated_at | timestamp | NO | |

### 3.2 `groups`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| company_id | FK | NO | |
| name | string | NO | |
| parent_id | bigint FK → groups.id | YES | nullable |
| sort_order | int default 0 | NO | ツリー表示順 |
| created_at / updated_at | timestamp | NO | |

### 3.3 `user_company`（所属）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| user_id | FK | NO | |
| company_id | FK | YES | nullable の場合は「部署のみ」等の運用を文書化 |
| department_id | FK | YES | nullable |
| primary | boolean | NO | 主所属 |
| started_on / ended_on | date | YES | nullable、履歴管理する場合 |
| created_at / updated_at | timestamp | NO | |

複合ユニークは要件次第（同一ユーザー複数所属可ならユニークにしない）。

---

## 4. API 詳細

### 4.1 GET `/api/companies`

一覧または `?tree=1` でネスト JSON:

```json
{
  "data": [
    {
      "id": 1,
      "name": "本社",
      "children": []
    }
  ]
}
```

### 4.2 GET `/api/groups`

`company_id` 必須クエリでフィルタ。`tree=1` で階層。

### 4.3 ユーザー所属の付与

ユーザー管理または本モジュールで `PUT /api/users/{id}/memberships` のように切り出してもよい。

---

## 5. 業務ルール

- `parent_id` 更新時に循環参照にならないよう、アプリ層で祖先チェック。
- 削除時は子がいる場合は 422 またはカスケード方針を明示。

---

## 6. フロント

| 画面 | 内容 |
|------|------|
| グループツリー | 再帰コンポーネントまたはフラット＋インデント |

---

## 7. Laravel クラス（案）

`CompanyController`, `GroupController`, `CompanyPolicy`, `GroupService`（ツリー組み立て）。

---

## 8. テスト観点

- ツリー API の親子整合
- 循環参照更新が拒否されること
