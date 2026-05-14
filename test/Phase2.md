# Phase2 実装チェックリスト（API/DB/UI/テスト）

## 完了の定義（固定）

**Phase2 のチェックリスト完了**とは、**本文書の §0〜§5 のすべての項目が [x] であること**とする。各項目は（1）実装が要件を満たす、（2）横断設計 `doc/detailed_design/90_横断タスク_詳細設計.md` に反映済み、（3）運用ルール §5 がリポジトリ上で明示されている、のいずれかまたは組み合わせで満たす。

---

## 0. このチェックリストの前提（`.cursor` 優先ルール準拠）

- [x] 参照優先順位を固定する  
      `doc/000_Requirements Specification.md` → `doc/100_Step.md` → `doc/basic_design/` → `doc/detailed_design/` → `doc/*90_横断タスク*`
- [x] Phase2 対象を固定する  
      勤怠管理 + ワークフロー（有給申請）
- [x] API 共通規約を固定する  
      成功: `{ "data": ... }` / 一覧: `{ "data": [...], "meta": {...} }` / 失敗: `{ "message": "...", "errors": {...} }`
- [x] DB 共通規約を固定する  
      `created_at` / `updated_at` は **NOT NULL**（`nullableTimestamps()` 不使用）
- [x] 監査ログ方針を固定する  
      「誰が・いつ・どのリソースに対して・何を変更したか」を追跡可能にする
- [x] テスト方針を固定する  
      変更単位ごとに単体 + 結合テストを同時更新、最低「正常系1 + 異常系1」

---

## 1. 横断（API/認可/監査/品質）

詳細は `doc/detailed_design/90_横断タスク_詳細設計.md` §11 以降。

### 1.1 API 共通

- [x] API ベースは `/api` に統一
- [x] 認証方式（Sanctum: Bearer または Cookie）をエンドポイント単位で明記
- [x] ステータスコード運用を統一（401/403/404/422/429/500）
- [x] バリデーションエラーの `errors` 形式を統一
- [x] 一覧 API の `meta`（ページ情報）を統一
- [x] `X-Request-Id` の受け取り/採番とログ出力方針を統一

### 1.2 認可・権限制御

- [x] 本人のみ操作 API を明示（勤怠打刻など）
- [x] 管理者のみ参照 API を明示（他ユーザー勤怠参照など）
- [x] 承認者のみ操作 API を明示（approve/reject）
- [x] Policy / ミドルウェア / Controller の責務分離を確認

### 1.3 監査ログ

- [x] 監査対象イベント一覧を定義（作成/更新/削除/状態遷移）
- [x] 勤怠: 打刻・修正を監査対象に含める
- [x] ワークフロー: 作成/更新/提出/取下/承認/却下/状態変更を監査対象に含める
- [x] 監査ログの最低項目（actor, action, resource, before/after, timestamp）を確定
- [x] 個人情報/機密情報のマスキング方針を確定

### 1.4 完了条件（横断）

- [x] API 共通規約に違反するレスポンスがない
- [x] 権限誤りで 403、未認証で 401 が返る
- [x] 重要操作が監査ログに記録される

---

## 2. 勤怠管理（Phase2）

参照:  
`doc/basic_design/04_Phase2_勤怠管理_基本設計.md`  
`doc/detailed_design/04_Phase2_勤怠管理_詳細設計.md`

### 2.1 DB

- [x] `attendance_records` マイグレーション作成
- [x] カラム定義: `user_id`, `type`, `recorded_at`, `source`, `created_at`, `updated_at`
- [x] `created_at` / `updated_at` が NOT NULL
- [x] インデックス `(user_id, recorded_at)` 作成
- [x] `type`（`clock_in`/`clock_out`）の制約を実装（enum or validation）
- [x] 外部キー制約（`user_id`）設定

### 2.2 API

- [x] `POST /api/attendance/clock-in` 実装
- [x] `POST /api/attendance/clock-out` 実装
- [x] `GET /api/attendance/records` 実装（`from`, `to`, `user_id`）
- [x] `GET /api/attendance/summary` 実装（`month` or `from`/`to`）
- [x] 出勤済み再打刻で 422（`already_clocked_in` 等）を返す
- [x] 未出勤退勤で 422 を返す
- [x] 当日判定を `Asia/Tokyo` で統一
- [x] 集計ロジックを Service に分離（例: `AttendanceSummaryService`）

### 2.3 UI

- [x] 打刻画面に出勤/退勤ボタンを実装
- [x] 直近ステータス（最終打刻時刻/状態）を表示
- [x] 勤怠一覧（表またはカレンダー）を実装
- [x] 月切替/期間指定 UI を実装
- [x] API エラー（422/403/500）をトーストまたはフォームエラーで表示
- [x] ダッシュボード「今日の勤怠」と整合する表示を確認

### 2.4 テスト

- [x] 単体: 勤怠集計サービス（正常系）  
      例: 1日の `clock_in`/`clock_out` から勤務分を計算できる
- [x] 単体: 勤怠集計サービス（異常系）  
      例: 打刻ペア不整合時の扱い
- [x] 結合: `clock-in` API（正常系: 初回打刻は 201）
- [x] 結合: `clock-in` API（異常系: 二重出勤は 422）
- [x] 結合: `clock-out` API（異常系: 未出勤退勤は 422）
- [x] 結合: `summary` API（月末境界を含む）

### 2.5 完了条件（勤怠）

- [x] 「打刻 → 履歴確認 → 集計確認」を 1 ユーザーストーリーで通せる
- [x] 権限・バリデーション・監査ログを含めて成立する

---

## 3. ワークフロー（有給申請）（Phase2）

参照:  
`doc/basic_design/05_Phase2_ワークフロー_基本設計.md`  
`doc/detailed_design/05_Phase2_ワークフロー_詳細設計.md`

### 3.1 DB

- [x] `requests` マイグレーション作成
- [x] `approvals` マイグレーション作成
- [x] `approval_rules` マイグレーション作成
- [x] `approval_rule_versions` マイグレーション作成
- [x] `request_rule_resolutions` マイグレーション作成
- [x] `requests` カラム定義: `user_id`, `type`, `status`, `payload(jsonb)`, `current_step`, `created_at`, `updated_at`
- [x] `approvals` カラム定義: `request_id`, `step_order`, `approver_user_id`, `result`, `comment`, `acted_at`, `created_at`, `updated_at`
- [x] `approval_rules` カラム定義: `name`, `request_type`, `scope_type`, `scope_id`, `priority`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`
- [x] `approval_rule_versions` カラム定義: `approval_rule_id`, `version_no`, `conditions(jsonb)`, `steps(jsonb)`, `is_published`, `published_at`, `created_by`, `created_at`, `updated_at`
- [x] `request_rule_resolutions` カラム定義: `request_id`, `approval_rule_id`, `approval_rule_version_id`, `resolved_steps(jsonb)`, `matched_context(jsonb)`, `created_at`, `updated_at`
- [x] `created_at` / `updated_at` が NOT NULL
- [x] `payload` の `schema_version` 運用方針を定義
- [x] 外部キーと必要インデックスを設定

### 3.2 API

- [x] `GET /api/requests` 実装（自分の申請/承認待ち切替）
- [x] `POST /api/requests` 実装（下書き作成）
- [x] `GET /api/requests/{id}` 実装（詳細）
- [x] `POST /api/requests/{id}/submit` 実装（draft → pending）
- [x] `POST /api/requests/{id}/approve` 実装
- [x] `POST /api/requests/{id}/reject` 実装
- [x] `GET /api/workflow/rules` 実装（admin 以上）
- [x] `POST /api/workflow/rules` 実装（admin 以上）
- [x] `PATCH /api/workflow/rules/{id}` 実装（admin 以上）
- [x] `POST /api/workflow/rules/{id}/publish` 実装（admin 以上）
- [x] `POST /api/workflow/rules/{id}/activate` 実装（admin 以上）
- [x] `POST /api/workflow/rules/{id}/deactivate` 実装（admin 以上）
- [x] `GET /api/requests/{id}/resolution` 実装（適用ルール確認）
- [x] 承認順序スキップ防止（`current_step` 検証）
- [x] 却下後の承認禁止（状態遷移ガード）
- [x] 申請者/承認者以外のアクセス拒否（403）
- [x] ルール管理 API の admin 未満アクセス拒否（403）
- [x] submit 時のルール解決（priority 評価 + デフォルトフォールバック）を実装

### 3.3 UI

- [x] 申請一覧画面（自分の申請タブ）を実装
- [x] 承認待ち一覧画面（承認者タブ）を実装
- [x] 新規申請フォーム（有給: 開始日/終了日/理由）を実装
- [x] 申請詳細の承認タイムライン表示を実装
- [x] 承認/却下アクション UI を実装（コメント入力含む）
- [x] 遷移不可状態（却下済み等）で操作ボタンを無効化

### 3.4 テスト

- [x] 単体: ワークフロー遷移サービス（正常系）  
      例: submit で pending、最終 approve で approved
- [x] 単体: ワークフロー遷移サービス（異常系）  
      例: step 不一致/不正承認者で拒否
- [x] 単体: 承認ルール解決サービス（正常系）  
      例: 複数候補から priority 最大の一致ルールを採用
- [x] 単体: 承認ルール解決サービス（異常系）  
      例: 一致なし時にデフォルトルールへフォールバック
- [x] 単体: 現在承認者解決（`WorkflowRequestApprovalService`）
- [x] 結合: `POST /api/requests`（正常系: draft 作成）
- [x] 結合: `POST /api/requests/{id}/approve`（正常系: 現在承認者は成功）
- [x] 結合: `POST /api/requests/{id}/approve`（異常系: 承認順飛ばし不可）
- [x] 結合: `POST /api/requests/{id}/reject` 後に `approve` 不可を検証
- [x] 結合: `POST /api/workflow/rules`（異常系: admin 未満は 403）
- [x] 結合: `POST /api/requests/{id}/submit` で `request_rule_resolutions` 保存を検証

### 3.5 完了条件（ワークフロー）

- [x] 「申請作成 → 提出 → 承認/却下 → 履歴確認」を 1 ユーザーストーリーで通せる
- [x] 状態遷移と権限チェックが破綻しない
- [x] 監査ログで申請ライフサイクルを追跡できる

---

## 4. リリース前チェック（Phase2 全体）

- [x] MVP 要件（勤怠 + ワークフロー）を満たす
- [x] ダッシュボード連携（今日の勤怠 / 未承認申請）を満たす
- [x] API 仕様差分をドキュメントに反映
- [x] テストが最低ライン（正常系1 + 異常系1）を満たす
- [x] `php artisan test` が通る
- [x] フロントテスト（`pnpm test`）が通る
- [x] フロントビルド（`pnpm run build`）が通る

---

## 5. 運用ルール（使い方）

本リポジトリでは次を **Phase2 チェックリスト運用** とする（以降の変更でも遵守）。

- [x] 着手時に「今回変更するチェック項目」に印を付ける  
      → PR・コミットの説明に対象セクション（例: §3.2）を書く。
- [x] PR 作成前に「完了条件」セクションのみ先に自己点検する  
      → 少なくとも該当フェーズの §2.5 / §3.5 / §4 を読み、テストをローカル実行する。
- [x] 未完了項目は「未着手 / 保留理由 / 次アクション」を追記する  
      → 新規に [ ] が増えた場合は `doc/310_Phase2_実装状況.md` に保留理由を書く。
- [x] 依頼範囲外のリファクタはこのチェックリストに含めない  
      → 大規模リファクタは別チケットとする。
