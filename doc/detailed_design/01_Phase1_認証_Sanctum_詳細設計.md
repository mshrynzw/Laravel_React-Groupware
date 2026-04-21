# 詳細設計書：認証（Laravel Sanctum）

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/01_Phase1_認証_Sanctum_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. 方式

| 項目 | 採用案 |
|------|--------|
| SPA | Vite + React が Laravel と同一オリジンまたは CORS 許可ドメインから API を呼ぶ |
| 認証 | Laravel Sanctum の SPA 認証（Cookie + CSRF）または API トークン。本詳細は **Bearer トークン（`personal_access_tokens`）** を主とする（実装時に either に固定） |

---

## 3. ルート定義（案）

`routes/api.php` に配置。ログインのみ `auth:sanctum` 以外。

| メソッド | URI | ミドルウェア | 説明 |
|----------|-----|----------------|------|
| POST | `/api/login` | `throttle:login` | ログイン |
| POST | `/api/logout` | `auth:sanctum` | ログアウト |
| GET | `/api/me` | `auth:sanctum` | 自身のユーザー情報 |

`throttle:login` は `RouteServiceProvider` 等で `6,1`（1分6回）など定義。

---

## 4. API 詳細

### 4.1 POST `/api/login`

**Request**

```json
{
  "email": "user@example.com",
  "password": "plain",
  "device_name": "web"
}
```

| フィールド | 型 | 必須 | ルール |
|------------|-----|------|--------|
| email | string | ○ | email |
| password | string | ○ | min:1 |
| device_name | string | △ | トークン識別用。省略時は `"web"` |

**処理**

1. `Auth::attempt(['email'=>$email,'password'=>$password])`
2. 失敗時 422 または 401（方針: ブルートフォース対策のため **常に同じメッセージ** で 422 を返すか、401 で統一するかを決定）
3. 成功時 `user->createToken($device_name)`、古いトークン失効ポリシーは任意

**Response 200**

```json
{
  "token": "1|xxxxxxxx",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "山田",
    "email": "user@example.com"
  }
}
```

### 4.2 POST `/api/logout`

**Request**  
Body なし可。

**処理**  
`$request->user()->currentAccessToken()->delete()`（現在のトークンのみ失効）。

**Response**  
204 No Content または `{"message":"OK"}`。

### 4.3 GET `/api/me`

**Response 200**

```json
{
  "data": {
    "id": 1,
    "name": "山田",
    "email": "user@example.com",
    "roles": ["admin"]
  }
}
```

`roles` はユーザー管理実装後に付与。

---

## 5. データベース

| テーブル | 備考 |
|----------|------|
| `users` | 既存（`created_at` / `updated_at` は **NOT NULL 必須**。他詳細設計に従う） |
| `personal_access_tokens` | Sanctum マイグレーション |

### `personal_access_tokens`（Sanctum 標準に準拠）

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | |
| tokenable_type / tokenable_id | morph | NO | |
| name | string | NO | `device_name` 等 |
| token | string(64) | NO | ハッシュ保存 |
| abilities | text nullable | YES | |
| last_used_at | timestamp | YES | nullable |
| expires_at | timestamp | YES | nullable |
| created_at / updated_at | timestamp | NO | |

---

## 6. Laravel クラス（案）

| クラス | 役割 |
|--------|------|
| `App\Http\Controllers\Api\AuthController` | login, logout, me |
| `App\Http\Requests\LoginRequest` | バリデーション |

---

## 7. フロント（React）

| 項目 | 内容 |
|------|------|
| ページ | `/login`：メール・パスワード、`POST /api/login` |
| トークン保持 | `localStorage` または `sessionStorage`（XSS リスクを文書化）。Cookie のみ運用なら HttpOnly はサーバセッション側 |
| 付与 | 以降の API に `Authorization: Bearer` |
| ガード | 未ログインで `/dashboard` 等へアクセスしたら `/login` へリダイレクト |

---

## 8. CSRF（SPA + Cookie セッションを併用する場合）

1. `GET /sanctum/csrf-cookie` を先に呼ぶ  
2. `axios.defaults.withCredentials = true`  
3. 以降 POST は自動で CSRF ヘッダ付与  

トークン方式のみの場合は CSRF は API の POST に不要（Bearer のみ）。

---

## 9. テスト観点

- 正しい資格情報で 200 とトークン取得
- 誤った資格情報で 401/422
- `/api/me` がトークンなしで 401
- ログアウト後に同一トークンで 401
