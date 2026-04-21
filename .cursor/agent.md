# エージェント向けプロジェクト指針

本ファイルは **`.cursor/agent.md`** に配置する。ルートの `AGENTS.md` と併用する場合は、内容の重複に注意する。

このリポジトリは **Web ベースのグループウェア** を目的とする。**バックエンドは Laravel（API）**、**フロントエンドは React（Vite）+ React Router**、DB は **PostgreSQL**（Laravel と **同一ホスト**。本番の API 側は **ConoHa VPS**）、認証は **Laravel Sanctum** を想定する。**本番のフロント静的配信は Cloudflare**、本番以外は主に **ConoHa VPS**（詳細は `doc/000_Requirements Specification.md` §3）。

## 応答言語

- ユーザーへの説明は **日本語** を用いる。

## ドキュメントの優先順位

設計・実装の判断に迷ったときは、次の順で参照する。

1. `doc/000_Requirements Specification.md` — 要件
2. `doc/100_Step.md` — WBS・フェーズ
3. `doc/basic_design/` — 各機能の基本設計
4. `doc/detailed_design/` — API・DB・処理の詳細（実装レベル）
5. `doc/200_Design_Database.mermaid` — ER 図（参考）

横断的な API 形式・監査ログ方針は `doc/basic_design/90_横断タスク_基本設計.md` および `doc/detailed_design/90_横断タスク_詳細設計.md` を優先する。

## コード配置（現状）

- **フロントエンド**: `src/frontend/`（Vite + React）
- **バックエンド**: リポジトリ構成は拡張に応じて追加される想定。Laravel を置く場合はプロジェクトルールに合わせて `src/backend` やルート直下など、既存構成に従う。

## 実装時の注意（設計書との整合）

- DB の **`created_at` / `updated_at`** は原則 **NOT NULL**（詳細設計の共通規約）。
- **監査ログ**: 各機能で「誰が・いつ・何を変更したか」を基本設計の非機能に沿って検討する。
- 変更は **依頼範囲に限定**し、無関係なリファクタやドキュメントの追加を避ける。

## テスト

- **コードを生成・変更する際は必ず、単体テストおよび結合テストのコードも同時に作成・更新する**。
- テストは **実装と同じ変更単位** で追加し、少なくとも **正常系 1 件 + 異常系 1 件** を含める。
- **API / 画面 / DB スキーマ** に影響する変更では、結合テスト（エンドツーエンド寄りの経路確認）を必須とする。
- 実装完了時はテスト実行を前提とし、最低でも `php artisan test` の通過を確認できる状態を目標にする。

## Cursor

- **自動適用ルール**（`alwaysApply: true`）: `.cursor/rules/project.mdc` — 上記の要約を Cursor が会話に取り込む。細部は本 `agent.md` と `doc/` に従う。
- 追加のファイル別ルールが必要なら、同じ `.cursor/rules/` に `.mdc` を増やす（例: `globs` で `*.tsx` だけ等）。
