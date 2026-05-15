<?php

namespace App\Models;

use Database\Factories\WikiPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WikiPage extends Model
{
    /** @use HasFactory<WikiPageFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'body',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WikiPage::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(WikiRevision::class, 'wiki_page_id')->orderByDesc('id');
    }
}
