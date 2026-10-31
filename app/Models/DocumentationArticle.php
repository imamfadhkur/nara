<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Model Dokumentasi & Basis Pengetahuan Sistem NARA.
 *
 * @property int $id
 * @property string $category
 * @property string $category_name
 * @property string $title
 * @property string $slug
 * @property string $icon
 * @property string $excerpt
 * @property string $content
 * @property int $sort_order
 * @property bool $is_published
 * @property string|null $last_updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DocumentationArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'category_name',
        'title',
        'slug',
        'icon',
        'excerpt',
        'content',
        'sort_order',
        'is_published',
        'last_updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Dapatkan konten ter-render dalam format HTML via parser Markdown bawaan Laravel.
     */
    public function getRenderedContentAttribute(): string
    {
        return Str::markdown($this->content ?? '');
    }
}

