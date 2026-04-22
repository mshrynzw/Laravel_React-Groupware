### 1. 認証フロー
- [OK] `/login` で `superadmin@example.com / password` でログイン成功
- [OK] ログイン後 `/dashboard` に遷移
- [OK] ヘッダーのログアウトで `/login` に戻る
- [OK] `/register` で新規ユーザー作成後、ログイン状態になる
- [OK] `/forgot-password` でメール送信APIが成功する
- [OK] `/password-reset?token=...&email=...` で再設定成功（token有効時）

---

### 2. メール認証フロー
- [OK] 未認証ユーザーでログイン時 `/email-verification` へ誘導
- [OK] 「認証メールを再送」押下で成功メッセージ表示
- [OK] 認証リンクアクセスで `/verify-email/:id/:hash` が成功し `/dashboard` へ戻る

---

### 3. ユーザー管理（`/users`）
- [OK] 一覧表示ができる
- [OK] 検索（名前/メール）が効く
- [OK] ページングが動く
- [OK] 作成（仮パスワード表示）が動く
- [OK] 編集（名前/ロール/所属/主グループ）が動く
- [OK] 削除が動く
- [OK] `primary_group_id` を所属外で指定するとエラーになる

---

### 4. グループ管理（`/groups`）
- [OK] 一覧表示ができる
- [OK] 検索が効く
- [OK] ページングが動く
- [OK] 作成が動く
- [OK] 編集が動く
- [OK] 削除が動く

---

### 5. 権限制御
- [OK] `member` はユーザー/グループ作成が 403
- [OK] `admin` は所属外ユーザー参照/操作が 403
- [OK] `superadmin` は全体操作可能

---

### 6. 監査ログ
- [OK] `auth.login` / `auth.login_failed` が記録される
- [OK] `user.created|updated|deleted` が記録される
- [OK] `group.created|updated|deleted` が記録される

（必要なら DB で `audit_logs` テーブル確認）

---

### 7. 自動テスト
- [OK] `php artisan test` が全PASS（現状9件）
- [OK] `pnpm build` がPASS

