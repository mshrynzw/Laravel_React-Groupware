# Phase6 進捗状況（高度機能）

**社内検索**・**給与計算** を実装済み。チェックリストは `test/Phase6.md`。

## サマリ

| 領域 | 状態 |
|------|------|
| 社内検索 | `SearchService`（DB / ES）、`user`・`file` 含む 5 種、`search:reindex`、フロント `/search` |
| 給与計算 | 勤怠連携・`PayrollTaxService`（schema v2）、PDF ダウンロード、フロント `/payroll` |

## 参照

- `doc/basic_design/12_Phase6_社内検索_基本設計.md`
- `doc/basic_design/13_Phase6_給与計算_基本設計.md`
- `doc/detailed_design/12_Phase6_社内検索_詳細設計.md`
- `doc/detailed_design/13_Phase6_給与計算_詳細設計.md`
- `doc/detailed_design/90_横断タスク_詳細設計.md` §17.1d
