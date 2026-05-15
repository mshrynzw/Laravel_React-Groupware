# Phase6 実装チェックリスト（高度機能）

対象: **Phase6**（`doc/100_Step.md`）— **社内検索**・**給与計算**。

---

## 完了の定義（固定）

**Phase6 のチェックリスト完了**とは、**本文書の §0〜§4 のすべての項目が [x] であること**とする。

---

## 0. 前提

- [x] 参照優先順位はプロジェクト共通ルールどおり
- [x] Phase6 対象を `doc/100_Step.md` の PHASE6 に合わせる
- [x] API は `/api` 配下、認証は Sanctum SPA Cookie

---

## 1. 社内検索

- [x] `GET /api/search`（`q` 必須、`type` 省略可、`announcement` / `wiki` / `task`）
- [x] 権限フィルタ（お知らせ公開・タスクスコープ・Wiki 閲覧）
- [x] フロント `/search`（API 連携・種別フィルタ）
- [x] 結合テスト `Phase6SearchTest`

---

## 2. 給与計算

- [x] `payroll_runs` / `payroll_slips` マイグレーション・モデル
- [x] `POST /api/payroll/runs`（管理者、同一月 completed は 409）
- [x] `GET /api/payroll/slips`・`GET /api/payroll/slips/{id}`（本人／管理者）
- [x] `PayrollCalculationService`（勤怠サマリ連携・内訳 JSON）
- [x] 監査 `payroll.run_started` / `payroll.run_completed` / `payroll.slip_viewed`
- [x] フロント `/payroll`（明細表示・管理者の計算実行）
- [x] 結合テスト `Phase6PayrollTest`

---

## 3. リリース前

- [x] `php artisan test` 通過
- [x] `pnpm run build` 通過

---

## 4. 拡張（任意・実装済み）

- [x] 横断検索に **ユーザー**・**ファイル**（ファイル名）を追加
- [x] **Elasticsearch / OpenSearch** 連携（`SEARCH_DRIVER=elasticsearch`・`php artisan search:reindex`・`docker-compose.opensearch.yml`）
- [x] 給与 **税・社保の段階的計算**（`PayrollTaxService`・`breakdown.schema_version` 2）
- [x] 給与明細 **PDF ダウンロード**（`GET /api/payroll/slips/{id}/download`）
