# 詳細設計書：社内検索

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/12_Phase6_社内検索_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. 検索方式（段階的）

| 段階 | 方式 | 備考 |
|------|------|------|
| 初版 | PostgreSQL `to_tsvector` + `plainto_tsquery` | テーブルごと UNION |
| 拡張 | 専用インデックス列を各テーブルに持ち GIN | |
| 大規模 | OpenSearch / Elasticsearch | 運用コストあり |

本詳細は **PostgreSQL 全文検索** を前提に記載する。

---

## 3. ルート（案）

| メソッド | URI |
|----------|-----|
| GET | `/api/search` |

---

## 4. API 詳細

### GET `/api/search`

**Query**

| パラメータ | 説明 |
|------------|------|
| q | 検索語（必須、max 200 文字） |
| type | `announcement`, `wiki`, `task` カンマ区切り。省略は全種 |
| page | |
| per_page | max 30 |

**Response 200**

```json
{
  "data": [
    {
      "type": "wiki",
      "id": 12,
      "title": "VPN 手順",
      "snippet": "...キーワード周辺の抜粋...",
      "url": "/wiki/vpn-setup"
    }
  ],
  "meta": { }
}
```

`snippet` は `ts_headline` 相当をサーバ生成。

---

## 5. 権限フィルタ

各ヒットについて Policy と同等の条件で除外:

- お知らせ: 未公開は管理者のみ
- Wiki: 公開ページのみ（将来「部門限定」なら JOIN）
- タスク: 本人または共有メンバーのみ

実装は `SearchService` が種別ごとにクエリを分岐し、**ユーザー ID を必ずバインド**。

---

## 6. インデックス（PostgreSQL 例）

各対象テーブルに `search_vector tsvector` を GENERATED 列で追加し GIN index。  
またはマテリアライズドビュー `search_documents` に統合し 1 テーブルで検索。

---

## 7. Laravel クラス（案）

`SearchController`, `SearchService`, `SearchResultResource`。

---

## 8. パフォーマンス

- クエリタイムアウト 2s
- `per_page` 上限
- ログに `q` を残す際は長さ制限

---

## 9. テスト観点

- 権限のないリソースがヒットしない
- 空クエリ 422
