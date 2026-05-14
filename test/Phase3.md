# Phase3 実装チェックリスト（API/DB/UI/テスト）

対象: **情報共有**（`doc/100_Step.md` PHASE3）— **お知らせ** + **ファイル共有**。

---

## 完了の定義（固定）

**Phase3 のチェックリスト完了**とは、**本文書の §0〜§5 のすべての項目が [x] であること**とする。各項目は（1）実装が設計どおり満たす、（2）`doc/detailed_design/90_横断タスク_詳細設計.md` および領域別詳細設計に反映済み、（3）§5 の運用ルールがリポジトリ上で明示されている、のいずれかまたは組み合わせで満たす。

---

## 0. 前提（`.cursor` / Phase2 との関係）

- [x] 参照優先順位は `doc/000_Requirements Specification.md` → `doc/100_Step.md` → `doc/basic_design/` → `doc/detailed_design/` → `doc/*90_横断*`
- [x] Phase3 対象を固定する（本書ヘッダーどおり）
- [x] API 共通規約は Phase2 で固定済み（`test/Phase2.md` §0 と `90_横断` §11 以降）。Phase3 の新 API もこれに従う。
- [x] DB 共通規約（`created_at` / `updated_at` NOT NULL）
- [x] 監査ログ方針（Phase2 で確立。Phase3 予定イベントは `90` §17.1a に記載）
- [x] テスト方針（変更単位ごとに単体 + 結合、正常系1 + 異常系1）

---

## 1. 横断（Phase3 での確認）

Phase2 で実装済みの基盤を **Phase3 のルートでも破壊しないこと** を確認する。

### 1.1 API 共通

- [x] Phase3 の各エンドポイントが `/api` 配下である
- [x] 認証・CSRF 方針が Phase2（Sanctum SPA Cookie）と矛盾しない
- [x] 一覧 API が Laravel `paginate` の `data` / `meta` と整合する（または設計どおりの理由で例外を文書化）
- [x] レスポンスに `X-Request-Id` が引き続き付与される（回帰確認）

### 1.2 認可

- [x] お知らせ: 一般と管理者の一覧・詳細・CRUD が設計どおり
- [x] ファイル: 本人・管理者スコープが `07_詳細設計` §11 どおり
- [x] Policy または Controller 認可のどちらかに統一し、`90` §16 に一言追記する

### 1.3 監査

- [x] `announcement.*` / `file.*` イベントが `audit_logs` に残る
- [x] `AuditLogger` 経由で `request_id` が付く（Phase2 と同様）

### 1.4 横断完了条件

- [x] 権限誤りで 403、未認証で 401（Phase3 API の結合テストで確認）
- [x] 重要操作が監査ログに記録される

---

## 2. お知らせ（Phase3）

参照:  
`doc/basic_design/06_Phase3_お知らせ_基本設計.md`  
`doc/detailed_design/06_Phase3_お知らせ_詳細設計.md`

### 2.1 DB

- [x] `announcements` マイグレーション作成
- [x] カラム: `title`, `body`, `author_user_id`, `published_at`（nullable）, `created_at`, `updated_at`（NOT NULL）
- [x] 任意: `updated_by`（nullable FK）— 詳細設計どおり取り込む場合
- [x] インデックス（公開一覧用: `published_at` 等）

### 2.2 API

- [x] `GET /api/announcements`（公開済みのみ、ページング・検索）
- [x] `GET /api/admin/announcements`（管理者、下書き・予約含む）
- [x] `GET /api/announcements/{id}`（公開済みのみ）
- [x] `POST /api/announcements`（管理者）
- [x] `PATCH /api/announcements/{id}`（管理者）
- [x] `DELETE /api/announcements/{id}`（管理者）

### 2.3 UI

- [x] お知らせ一覧（既存 `/announcements` ページの API 接続または差し替え）
- [x] 詳細表示
- [x] 管理: 作成・編集（管理者のみ到達可能なルート）

### 2.4 テスト

- [x] 単体: 公開判定ヘルパまたはスコープ（正常: 公開のみ／異常: 下書き除外）
- [x] 結合: 一般 `GET /api/announcements` に下書きが含まれない
- [x] 結合: `member` の `POST` が 403
- [x] 結合: 管理者一覧に下書きが含まれる

### 2.5 完了条件（お知らせ）

- [x] 「管理者が投稿 → 一般が一覧・詳細で閲覧」を通せる
- [x] XSS 方針（サニタイズまたはプレーンテキスト）が文書と実装で一致

---

## 3. ファイル共有（Phase3）

参照:  
`doc/basic_design/07_Phase3_ファイル共有_基本設計.md`  
`doc/detailed_design/07_Phase3_ファイル共有_詳細設計.md`

### 3.1 DB

- [x] `files` マイグレーション作成
- [x] カラム: `user_id`, `original_name`, `disk`, `path`, `size`, `mime_type`, `visibility`, `created_at`, `updated_at`（NOT NULL）
- [x] 必要なインデックス（`user_id`, `created_at` 等）

### 3.2 API

- [x] `GET /api/files`（認可に沿った一覧）
- [x] `POST /api/files`（multipart）
- [x] `GET /api/files/{id}/download`（認可後ストリームまたは一時 URL）
- [x] `DELETE /api/files/{id}`

### 3.3 ストレージ

- [x] `config/filesystems.php` の disk（`local` / `s3`）が `.env` と整合
- [x] 本番想定: S3 バケット・IAM のメモが README または運用ドキュメントにあり、**秘匿情報はコミットしない**

### 3.4 UI

- [x] ファイル一覧
- [x] アップロード（進捗または完了メッセージ）

### 3.5 テスト

- [x] 単体: パス生成・メタデータ保存（正常系）
- [x] 単体: サイズ上限超過（異常系）
- [x] 結合: 他人ファイルのダウンロードが 403 または 404（設計で統一した方）
- [x] 結合: アップロード成功で `files` 行とストレージに実体がある

### 3.6 完了条件（ファイル）

- [x] 「アップロード → 一覧 → ダウンロード → 削除」を権限内ユーザーで通せる

---

## 4. リリース前チェック（Phase3 全体）

- [x] 要件 4.4 / 4.8 の MVP を満たす
- [x] ダッシュボード「最新のお知らせ」と整合（データ取得を API に切替）
- [x] `php artisan test` が通る
- [x] `pnpm test` および `pnpm run build` が通る

---

## 5. 運用ルール（Phase3）

- [x] 着手時に変更する § を PR 説明に書く
- [x] PR 前に §4 を自己点検する
- [x] スコープ外は `doc/311_Phase3_実装状況.md` のバックログに追記する
