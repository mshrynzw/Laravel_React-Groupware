# Phase3 進捗状況

このファイルは **進捗管理用**（情報共有: お知らせ・ファイル共有）。  
チェックリスト本体は `test/Phase3.md` に従う。

**Phase3 チェックリスト完了の定義**は `test/Phase3.md` 冒頭の「完了の定義（固定）」に従う（§0〜§5 全 [x]）。

---

## 現在の進捗サマリ

- **お知らせ**: MVP 実装済み（`announcements`、一般/管理 API、`AnnouncementController`、監査 `announcement.*`、フロント一覧・詳細・管理作成・編集・削除）
- **ファイル共有**: MVP 実装済み（`files`、`StoredFile` / `FileStorageService`、`FileController`、グループスコープ付き一覧・DL・削除、監査 `file.*`、フロント一覧・アップロード）
- **横断**: Phase2 で導入済みの `X-Request-Id`・監査方針を **Phase3 API でも踏襲**。監査イベント案は `90_横断タスク_詳細設計.md` §17.1a と各領域 §10。

**チェックリスト**: `test/Phase3.md` は §0〜§5 すべて [x]（実装・テスト・README/設計追記まで反映済み）。

### テスト追加メモ

- 監査: `tests/Feature/Phase3AuditLogTest.php`（`X-Request-Id` と `audit_logs.payload.request_id`）
- 横断: `tests/Feature/Phase3CrossCutTest.php`（401・ページネーション JSON・`X-Request-Id`）
- 公開スコープ単体: `tests/Unit/AnnouncementPublishedScopeTest.php`

---

## 参照

- チェックリスト: `test/Phase3.md`
- WBS: `doc/100_Step.md`（PHASE3：情報共有）
- 要件: `doc/000_Requirements Specification.md`（4.4 お知らせ、4.8 ファイル共有）
- 横断: `doc/detailed_design/90_横断タスク_詳細設計.md`
- DB 概念: `doc/200_Design_Database.mermaid`
