# Phase5 実装チェックリスト（ナレッジ / Wiki）

対象: **Phase5**（`doc/100_Step.md`）— **Wiki**。

---

## 完了の定義（固定）

**Phase5 のチェックリスト完了**とは、**本文書の §0〜§4 のすべての項目が [x] であること**とする。

---

## 0. 前提

- [x] 参照優先順位はプロジェクト共通ルールどおり
- [x] Phase5 対象を `doc/100_Step.md` の PHASE5（Wiki）に合わせる
- [x] API は `/api` 配下、認証は Sanctum SPA Cookie

---

## 1. データモデル

- [x] `wiki_pages` マイグレーション（`slug` unique、`title`、`body`、`parent_id` nullable、`created_by` / `updated_by`、タイムスタンプ）
- [x] `WikiPage` モデル・Factory

---

## 2. API

- [x] `GET /api/wiki/pages`（ページネーション・任意 `q`）
- [x] `GET /api/wiki/pages/by-slug/{slug}`（404）
- [x] `POST /api/wiki/pages`（**admin / superadmin** のみ）
- [x] `PUT /api/wiki/pages/{id}`（同上、**秒単位**の `updated_at` 楽観ロック・409）
- [x] `DELETE /api/wiki/pages/{id}`（同上）
- [x] 監査 `wiki.created` / `wiki.updated` / `wiki.deleted`

---

## 3. フロント

- [x] `/wiki` 一覧・検索、`/wiki/view/:slug` 表示（`react-markdown` + `remark-gfm`）
- [x] `/wiki/new`・`/wiki/edit/:slug`（管理者向け作成・編集・分割プレビュー）

---

## 4. リリース前

- [x] `php artisan test` 通過
- [x] `pnpm run build` 通過

---

## 5. 将来拡張（任意）

- [x] `wiki_revisions` 履歴テーブル・差分 UI
- [x] 階層ナビ・親子 UI
