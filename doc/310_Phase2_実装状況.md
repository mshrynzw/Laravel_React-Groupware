# Phase2 進捗状況

このファイルは **進捗管理用**。  
チェックリスト本体は `test/Phase2.md` に移行済み。

**Phase2 チェックリスト完了の定義**は `test/Phase2.md` 冒頭の「完了の定義（固定）」に従う（§0〜§5 全 [x]）。

---

## 現在の進捗サマリ

- **Phase2 チェックリスト: 完了**（`test/Phase2.md` 全セクション [x]、横断は `doc/detailed_design/90_横断タスク_詳細設計.md` §11 以降に根拠を記載）
- ワークフロー（有給申請）: **MVP 完了**（`WorkflowRequestTransitionService`、`payload.schema_version`、コメント付き承認／却下、テスト一式）
- 勤怠管理: **MVP 実装済み**（打刻 API・履歴・月次集計・監査・`X-Request-Id` 連携）
- 共通方針: **Phase2 範囲で文書・実装済み**（相関 ID、`AuditLogger` への `request_id` 付与）

---

## 完了済み（ワークフロー）

- DB: `requests`, `approvals`, `approval_rules`, `approval_rule_versions`, `request_rule_resolutions`
- API: requests 一式 + approve/reject（コメント任意）+ rule 管理 + resolution
- `payload.schema_version`: 未送信時は `1` をサーバ付与（`StoreWorkflowRequest`）
- 権限制御・監査・UI・単体/結合テスト: チェックリストどおり

---

## 完了済み（勤怠）

- DB / API / 権限 / 監査 / UI / テスト: チェックリストどおり

---

## Phase2 チェックリスト外のバックログ（任意）

以下は **`test/Phase2.md` の完了定義には含めない** メモである（Phase2 MVP 完了後も検討したいこと・別フェーズ候補）。

- 勤怠: 休憩打刻・修正フロー（要件次第。詳細は勤怠基本設計のスコープに従い `doc/detailed_design/04_...` に追記してもよい）
- API 仕様の OpenAPI 化（任意。クライアント増・外部連携が進むタイミングで検討）
- 認可の Policy 集約（任意。現状の所在は `doc/detailed_design/90_横断タスク_詳細設計.md` §16。肥大化したらリファクタ）

---

## 参照

- チェックリスト本体: `test/Phase2.md`
- 横断（完了根拠）: `doc/detailed_design/90_横断タスク_詳細設計.md`（§11 以降）
- DB設計: `doc/200_Design_Database.mermaid`
- ワークフロー基本設計: `doc/basic_design/05_Phase2_ワークフロー_基本設計.md`
- 勤怠詳細設計: `doc/detailed_design/04_Phase2_勤怠管理_詳細設計.md`
- ワークフロー詳細設計: `doc/detailed_design/05_Phase2_ワークフロー_詳細設計.md`
