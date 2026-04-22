# Phase1 実装状況（2026-04-21 時点）

## 1. 対象範囲

- 認証（Sanctum）
- ユーザー管理
- グループ管理

---

## 2. 実装済み機能

### 2.1 認証（Sanctum / SPA Cookie）

- `POST /api/login`（ログイン）
- `POST /api/logout`（ログアウト）
- `GET /api/me`（認証ユーザー取得）
- `POST /api/register`（新規登録）
- `POST /api/forgot-password`（リセットメール送信）
- `POST /api/reset-password`（パスワード再設定）
- `POST /api/email/verification-notification`（認証メール再送）
- `GET /api/verify-email/{id}/{hash}`（メール認証）

フロント画面:

- `/login`
- `/register`
- `/forgot-password`
- `/password-reset`
- `/email-verification`
- `/verify-email/:id/:hash`

備考:

- CSRF (`/sanctum/csrf-cookie`) + `X-XSRF-TOKEN` で連携済み
- 未認証時は `/login`、未メール認証時は `/email-verification` に誘導

### 2.2 ユーザー管理

- `GET /api/users`（検索・ページング対応）
- `GET /api/users/{id}`
- `POST /api/users`（仮パスワード発行）
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`

実装済み仕様:

- ロール: `superadmin` / `admin` / `member`
- 複数グループ所属
- `primary_group_id`（NULL許容）
- `admin` は所属グループ内のみ操作可（API制限）

フロント画面:

- `/users`
  - 一覧
  - 検索
  - ページング
  - 作成
  - 編集
  - 削除
  - 所属グループ・主グループ編集

### 2.3 グループ管理

- `GET /api/groups`（検索・ページング対応）
- `GET /api/groups/{id}`
- `POST /api/groups`
- `PUT /api/groups/{id}`
- `DELETE /api/groups/{id}`

実装済み仕様:

- `admin` は所属グループのみ操作可（API制限）
- `superadmin` は全体操作可

フロント画面:

- `/groups`
  - 一覧
  - 検索
  - ページング
  - 作成
  - 編集
  - 削除

### 2.4 監査ログ（横断）

- `audit_logs` テーブル追加済み
- 認証・ユーザー・グループの主要操作で記録

主な event:

- `auth.login` / `auth.login_failed` / `auth.logout`
- `auth.register`
- `auth.email_verification_notification_sent` / `auth.email_verified`
- `auth.password_reset_link_requested` / `auth.password_reset`
- `user.created` / `user.updated` / `user.deleted`
- `group.created` / `group.updated` / `group.deleted`

---

## 3. DB 実装状況

実装済み:

- `users.role`
- `users.primary_group_id`（FK / nullOnDelete）
- `groups`
- `group_user`
- `audit_logs`
- `personal_access_tokens`（Sanctum）

Seeder:

- `superadmin@example.com / password`
- `開発グループ`
- Seeder は再実行耐性あり（`firstOrCreate` / `updateOrCreate`）

---

## 4. テスト実装状況

Feature テスト:

- 認証ユーザー取得（`/api/me`）
- admin のスコープ外ユーザー参照禁止
- member の管理操作禁止
- primary_group_id の妥当性チェック
- register の正常系・異常系
- 監査ログ作成確認（ログイン失敗、グループ作成）

現状:

- `php artisan test` 通過

---

## 5. 未完了（Phase1 完了判定向け）

1. メール送信基盤の実運用設定（SMTP / Mailhog 等）
2. パスワードリセットの実メール経路での受け入れ確認
3. API 仕様書（OpenAPI または API md）の正式化
4. フロント統合テスト（RTL）の追加
5. 監査ログ参照用の管理画面（必要なら Phase1.5 で実施）

---

## 6. 受け入れ確認コマンド

```bash
cd src/backend
php artisan migrate
php artisan db:seed
php artisan test
```

```bash
cd src/frontend
pnpm build
pnpm dev
```
