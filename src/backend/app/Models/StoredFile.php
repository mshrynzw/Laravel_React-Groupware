<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ユーザーアップロードファイル（テーブル名: files）
 */
class StoredFile extends Model
{
    protected $table = 'files';

    protected $fillable = [
        'user_id',
        'original_name',
        'disk',
        'path',
        'size',
        'mime_type',
        'visibility',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
