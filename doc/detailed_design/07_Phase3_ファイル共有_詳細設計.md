# 詳細設計書：ファイル共有

## 1. 参照文書

| 種別 | パス |
|------|------|
| 基本設計 | `doc/basic_design/07_Phase3_ファイル共有_基本設計.md` |
| 横断 | `doc/detailed_design/90_横断タスク_詳細設計.md` |

---

## 2. ストレージ

| 環境 | disk | 備考 |
|------|------|------|
| 開発 | `local` | `storage/app/private/...` |
| 本番 | `s3` | バケットポリシー・暗号化 |

`.env`: `FILESYSTEM_DISK`, `AWS_*`。

---

## 3. データベース

### `files`

| カラム | 型 | NULL | 備考 |
|--------|-----|------|------|
| id | bigint PK | NO | UUID 公開用に `uuid` 列を追加してもよい |
| user_id | FK | NO | アップロード者 |
| original_name | string | NO | |
| disk | string | NO | `local`, `s3` |
| path | string | NO | バケット内キー |
| size | bigint | NO | バイト |
| mime_type | string | NO | |
| visibility | string | NO | `private` 固定推奨 |
| created_at / updated_at | timestamp | NO | |

アクセス制御を `file_user` 等で拡張する場合は別テーブル。

---

## 4. ルート（案）

| メソッド | URI | 説明 |
|----------|-----|------|
| GET | `/api/files` | 一覧 |
| POST | `/api/files` | multipart アップロード |
| GET | `/api/files/{id}/download` | ストリームまたは一時署名 URL リダイレクト |
| DELETE | `/api/files/{id}` | 削除 |

---

## 5. API 詳細

### POST `/api/files`

- `Content-Type: multipart/form-data`
- フィールド名 `file`
- 最大サイズ: `php.ini` と Laravel `max:10240` 等で整合

**Response 201**

```json
{
  "data": {
    "id": 1,
    "original_name": "report.pdf",
    "size": 1024,
    "mime_type": "application/pdf",
    "created_at": "..."
  }
}
```

### GET `/api/files/{id}/download`

1. Policy で閲覧可を確認  
2. S3: `Storage::temporaryUrl($path, now()->addMinutes(5))` で 302  
3. local: `Storage::response()` でストリーム  

---

## 6. Laravel クラス（案）

`FileController`, `FilePolicy`, `FileService`（保存・削除）。

---

## 7. フロント

- ドラッグ＆ドロップアップロード、プログレスバー
- 一覧からダウンロードリンク（GET で署名 URL が返る想定なら `window.location`）

---

## 8. テスト観点

- 他人のファイル GET が 403
- 大ファイルが上限で 422
