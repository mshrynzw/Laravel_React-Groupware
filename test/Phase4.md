# Phase4 実装チェックリスト（コラボ）

対象: **Phase4**（`doc/100_Step.md`）— **タスク管理**・**スケジュール**・**チャット**。

---

## 完了の定義（固定）

**Phase4 のチェックリスト完了**とは、**本文書の §0〜§4 のすべての項目が [x] であること**とする。

---

## 0. 前提

- [x] 参照優先順位はプロジェクト共通ルールどおり
- [x] Phase4 対象を `doc/100_Step.md` の PHASE4 に合わせる
- [x] API は `/api` 配下、認証は Sanctum SPA Cookie（Phase2 と同様）

---

## 1. タスク管理

- [x] `tasks` マイグレーション・モデル
- [x] `GET/POST/PATCH/PUT/DELETE /api/tasks`（グループスコープ・作成者・担当者）
- [x] ステータス `todo` / `in_progress` / `done`、バリデーション・監査 `task.*`
- [x] フロント `Tasks.tsx`（API 連携・DnD で PATCH）
- [x] 結合テスト（異常ステータス 422、担当者の削除不可等）

---

## 2. スケジュール

- [x] `schedules` マイグレーション・モデル
- [x] `GET /api/schedules?from&to&user_id?`（期間・本人／管理者スコープ）
- [x] `POST/GET/PATCH/PUT/DELETE /api/schedules`、監査 `schedule.*`
- [x] フロント `Schedule.tsx`（**月／週**表示切替・作成フォーム）
- [x] 結合テスト（終了＜開始 422、期間フィルタ、週境界の `from`/`to`、`to`＜`from` が 422）

---

## 3. チャット

- [x] `chat_rooms` / `chat_room_user` / `chat_messages` マイグレーション・モデル
- [x] `GET/POST /api/chat/rooms`（グループ作成・DM find-or-create）
- [x] `GET/POST /api/chat/rooms/{id}/messages`（カーソル `before_id`、監査 `chat.*`）
- [x] フロント `Chat.tsx`（ルーム一覧・Reverb/Echo またはポーリング・送信）
- [x] 結合テスト（非参加者 POST が 403、送信で `ChatMessageSent` ブロードキャスト）

---

## 4. リリース前

- [x] `php artisan test` 通過
- [x] `pnpm run build` 通過

---

## 5. 運用メモ（任意拡張）

- [x] リアルタイムは **Laravel Reverb + Laravel Echo**（`VITE_REVERB_*` 設定時）。未設定時は **HTTP ポーリング**（約 4 秒）。併用時は Echo 受信＋約 30 秒のバックアップポーリング。
