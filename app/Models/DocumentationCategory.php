<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Model Kategori Dokumentasi NARA.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property string|null $description
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DocumentationArticle> $articles
 */
class DocumentationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<DocumentationArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(DocumentationArticle::class, 'documentation_category_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<DocumentationArticle, $this>
     */
    public function publishedArticles(): HasMany
    {
        return $this->articles()->where('is_published', true);
    }
}

