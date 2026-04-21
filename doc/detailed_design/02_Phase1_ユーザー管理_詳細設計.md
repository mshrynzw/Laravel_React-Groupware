# 詳細設計書：ユーザー管理

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/02_Phase1_ユーザー管理_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ルート（案）

全て `auth:sanctum` + 権限（例: `can:manage-users` または Policy）。

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/users` | 一覧（ページング） |
| GET | `/api/users/{id}` | 詳細 |
| POST | `/api/users` | 作成 |
| PUT/PATCH | `/api/users/{id}` | 更新 |
| DELETE | `/api/users/{id}` | 削除（論理削除の場合は `deleted_at`） |

一般ユーザーは `GET /api/users/{id}` を自分のみ、または `GET /api/me` に寄せる。

---

## 3. データベース

### 3.1 `users`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| family_name | string | NO | |
| first_name | string | NO | |
| email | string unique | NO | |
| email_verified_at | timestamp | YES | |
| password | string | NO | bcrypt 等 |
| remember_token | string | YES | |
| created_at / updated_at | timestamp | NO | |
| deleted_at | timestamp | YES | SoftDeletes 採用時 |

※ `created_at` / `updated_at` の共通規約は `doc/detailed_design/90_横断タスク_詳細設計.md` を参照。

### 3.2 `roles`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| name | string | NO | |
| slug | string unique | NO | |
| created_at / updated_at | timestamp | NO | |

### 3.3 `role_user`（中間）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| user_id | FK → users | NO | |
| role_id | FK → roles | NO | |
| created_at / updated_at | timestamp | NO | |

複合 PK `(user_id, role_id)`。

---

## 4. API 詳細

### 4.1 GET `/api/users`

**Query**

| パラメータ | 例 | 説明 |
|------------|-----|------|
| page | 1 | |
| per_page | 15 | max 100 |
| q | 山田 | name/email 部分一致 |

**Response 200**  
`data` に User リソース配列、`meta` にページ情報（横断仕様）。

User リソース例:

```json
{
  "id": 1,
  "name": "山田",
  "email": "a@b.com",
  "roles": [{ "id": 1, "slug": "admin", "name": "管理者" }]
}
```

### 4.2 POST `/api/users`

**Request**

```json
{
  "name": "山田",
  "email": "a@b.com",
  "password": "secret",
  "password_confirmation": "secret",
  "role_ids": [1, 2]
}
```

バリデーション: `email` unique、`password` confirmed、`role_ids` exists。

### 4.3 PUT `/api/users/{id}`

パスワード省略可。変更時のみ `password` + `password_confirmation`。

### 4.4 DELETE `/api/users/{id}`

自分自身の削除禁止、最後の管理者削除禁止等は Service で検証。

---

## 5. Laravel クラス（案）

| クラス | 役割 |
|--------|------|
| `UserController` | CRUD |
| `UserPolicy` | 参照・更新・削除の可否 |
| `StoreUserRequest` / `UpdateUserRequest` | バリデーション |
| `UserService` | 作成・更新時のロール同期 |

---

## 6. フロント

| 画面 | コンポーネント例 | 操作 |
|------|-------------------|------|
| 一覧 | `UsersListPage` | テーブル、検索、ページャ |
| 編集 | `UserFormModal` | 作成・更新共用 |

---

## 7. テスト観点

- 管理者のみ CRUD が 200/201/204
- 一般ユーザーが他人の `GET /api/users/{id}` で 403
- バリデーション 422
