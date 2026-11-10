<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Model Dokumentasi & Basis Pengetahuan Sistem NARA.
 *
 * @property int $id
 * @property int|null $documentation_category_id
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
 * @property-read DocumentationCategory|null $documentationCategory
 */
class DocumentationArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentation_category_id',
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
            'documentation_category_id' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentationCategory, $this>
     */
    public function documentationCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'documentation_category_id');
    }

    /**
     * Alias relasi untuk kemudahan akses.
     * @return BelongsTo<DocumentationCategory, $this>
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->documentationCategory();
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->documentationCategory?->name ?? $this->attributes['category_name'] ?? 'Umum';
    }

    public function getCategorySlugAttribute(): string
    {
        return $this->documentationCategory?->slug ?? $this->attributes['category'] ?? 'general';
    }

    /**
     * Dapatkan konten ter-render dalam format HTML via parser Markdown bawaan Laravel.
     */
    public function getRenderedContentAttribute(): string
    {
        return Str::markdown($this->content ?? '');
    }
}
